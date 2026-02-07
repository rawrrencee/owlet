<?php

namespace App\Services;

use App\Constants\InventoryActivityCodes;
use App\Constants\StoreAccessPermissions;
use App\Enums\StocktakeStatus;
use App\Mail\StocktakeCompletedMail;
use App\Models\Employee;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Stocktake;
use App\Models\StocktakeItem;
use App\Models\StocktakeNotificationRecipient;
use App\Models\StocktakeTemplate;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class StocktakeService
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Get existing in-progress stocktake or create a new one.
     */
    public function getOrCreateStocktake(Employee $employee, Store $store): Stocktake
    {
        $existing = Stocktake::where('employee_id', $employee->id)
            ->where('store_id', $store->id)
            ->inProgress()
            ->first();

        if ($existing) {
            return $existing;
        }

        return Stocktake::create([
            'employee_id' => $employee->id,
            'store_id' => $store->id,
            'status' => StocktakeStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Add a product item to a stocktake with the counted quantity.
     */
    public function addItem(Stocktake $stocktake, int $productId, int $countedQty): StocktakeItem
    {
        $systemQty = $this->getSystemQuantity($productId, $stocktake->store_id);
        $hasDiscrepancy = $systemQty !== $countedQty;

        $item = StocktakeItem::create([
            'stocktake_id' => $stocktake->id,
            'product_id' => $productId,
            'system_quantity' => $systemQty,
            'counted_quantity' => $countedQty,
            'has_discrepancy' => $hasDiscrepancy,
        ]);

        $stocktake->recomputeHasIssues();

        return $item;
    }

    /**
     * Update the counted quantity for an existing stocktake item.
     */
    public function updateItem(StocktakeItem $item, int $countedQty): StocktakeItem
    {
        $hasDiscrepancy = $item->system_quantity !== $countedQty;

        $item->update([
            'counted_quantity' => $countedQty,
            'has_discrepancy' => $hasDiscrepancy,
        ]);

        $item->stocktake->recomputeHasIssues();

        return $item;
    }

    /**
     * Remove an item from a stocktake.
     */
    public function removeItem(StocktakeItem $item): void
    {
        $stocktake = $item->stocktake;
        $item->delete();
        $stocktake->recomputeHasIssues();
    }

    /**
     * Submit (finalize) a stocktake.
     */
    public function submit(Stocktake $stocktake): Stocktake
    {
        return DB::transaction(function () use ($stocktake) {
            // Re-snapshot system quantities for final comparison
            foreach ($stocktake->items as $item) {
                $systemQty = $this->getSystemQuantity($item->product_id, $stocktake->store_id);
                $item->update([
                    'system_quantity' => $systemQty,
                    'has_discrepancy' => $systemQty !== $item->counted_quantity,
                ]);
            }

            $stocktake->update([
                'status' => StocktakeStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $stocktake->recomputeHasIssues();

            // Send notification emails
            $this->sendNotifications($stocktake);

            return $stocktake->fresh(['items.product', 'employee', 'store']);
        });
    }

    /**
     * Delete an in-progress stocktake.
     */
    public function delete(Stocktake $stocktake): void
    {
        $stocktake->delete();
    }

    /**
     * Add all products from a template to a stocktake.
     */
    public function addItemsFromTemplate(Stocktake $stocktake, StocktakeTemplate $template): int
    {
        $added = 0;
        $existingProductIds = $stocktake->items()->pluck('product_id')->toArray();

        $products = $template->products()
            ->where('is_active', true)
            ->whereNull('products.deleted_at')
            ->get();

        foreach ($products as $product) {
            if (in_array($product->id, $existingProductIds)) {
                continue;
            }

            $systemQty = $this->getSystemQuantity($product->id, $stocktake->store_id);

            StocktakeItem::create([
                'stocktake_id' => $stocktake->id,
                'product_id' => $product->id,
                'system_quantity' => $systemQty,
                'counted_quantity' => 0,
                'has_discrepancy' => $systemQty !== 0,
            ]);

            $added++;
        }

        if ($added > 0) {
            $stocktake->recomputeHasIssues();
        }

        return $added;
    }

    /**
     * Check if user can view the stocktake difference for a store.
     */
    public function canViewDifference(User $user, int $storeId): bool
    {
        return $this->permissionService->canAccessStore(
            $user,
            $storeId,
            StoreAccessPermissions::STORE_VIEW_STOCKTAKE_DIFFERENCE
        );
    }

    /**
     * Expire old in-progress stocktakes.
     */
    public function expireOldStocktakes(): int
    {
        return Stocktake::inProgress()
            ->whereDate('created_at', '<', today())
            ->update(['status' => StocktakeStatus::EXPIRED]);
    }

    /**
     * Adjust product quantity (Lost/Found) and create inventory log.
     */
    public function adjustQuantity(
        Product $product,
        Store $store,
        int $adjustQty,
        ?Stocktake $stocktake,
        User $user
    ): InventoryLog {
        return DB::transaction(function () use ($product, $store, $adjustQty, $stocktake, $user) {
            $productStore = ProductStore::where('product_id', $product->id)
                ->where('store_id', $store->id)
                ->first();

            $currentQty = $productStore?->quantity ?? 0;
            $newQty = $currentQty + $adjustQty;

            // Update or create the product-store record
            if ($productStore) {
                $productStore->update(['quantity' => $newQty]);
            } else {
                ProductStore::create([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'quantity' => $newQty,
                    'is_active' => true,
                ]);
            }

            $activityCode = $adjustQty > 0
                ? InventoryActivityCodes::FOUND_ITEM
                : InventoryActivityCodes::LOST_ITEM;

            return InventoryLog::create([
                'product_id' => $product->id,
                'store_id' => $store->id,
                'stocktake_id' => $stocktake?->id,
                'activity_code' => $activityCode,
                'quantity_in' => $adjustQty > 0 ? $adjustQty : 0,
                'quantity_out' => $adjustQty < 0 ? abs($adjustQty) : 0,
                'current_quantity' => $newQty,
                'notes' => null,
                'created_by' => $user->id,
            ]);
        });
    }

    /**
     * Get the system quantity for a product at a store.
     */
    protected function getSystemQuantity(int $productId, int $storeId): int
    {
        $productStore = ProductStore::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        return $productStore?->quantity ?? 0;
    }

    /**
     * Send notification emails for a submitted stocktake.
     */
    protected function sendNotifications(Stocktake $stocktake): void
    {
        $recipients = StocktakeNotificationRecipient::where('store_id', $stocktake->store_id)
            ->active()
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $stocktake->load(['items.product', 'employee', 'store']);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)
                ->queue(new StocktakeCompletedMail($stocktake));
        }
    }
}
