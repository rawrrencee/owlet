<?php

namespace App\Services;

use App\Constants\InventoryActivityCodes;
use App\Enums\NotificationEventType;
use App\Enums\PurchaseOrderStatus;
use App\Mail\PurchaseOrderNotificationMail;
use App\Models\InventoryLog;
use App\Models\NotificationRecipient;
use App\Models\ProductStore;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PurchaseOrderService
{
    public function create(array $data, User $user): PurchaseOrder
    {
        $order = DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'order_number' => PurchaseOrder::generateOrderNumber(),
                'supplier_id' => $data['supplier_id'],
                'status' => PurchaseOrderStatus::DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'currency_id' => $item['currency_id'],
                    'unit_cost' => $item['unit_cost'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $order->load('items.product', 'items.currency');
        });

        $this->sendNotification($order, 'Created');

        return $order;
    }

    public function update(PurchaseOrder $order, array $data, User $user): PurchaseOrder
    {
        abort_unless($order->status === PurchaseOrderStatus::DRAFT, 422, 'Only draft orders can be edited.');

        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'supplier_id' => $data['supplier_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Sync items: delete all and recreate
            $order->items()->delete();

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'currency_id' => $item['currency_id'],
                    'unit_cost' => $item['unit_cost'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $order->load('items.product', 'items.currency');
        });
    }

    public function submit(PurchaseOrder $order, User $user): PurchaseOrder
    {
        abort_unless($order->status === PurchaseOrderStatus::DRAFT, 422, 'Only draft orders can be submitted.');
        abort_unless($order->items()->count() > 0, 422, 'Cannot submit an order with no items.');

        $order->update([
            'status' => PurchaseOrderStatus::SUBMITTED,
            'submitted_at' => now(),
            'submitted_by' => $user->id,
        ]);

        $this->sendNotification($order, 'Submitted');

        return $order;
    }

    public function accept(PurchaseOrder $order, int $storeId, array $receivedItems, User $user): PurchaseOrder
    {
        abort_unless($order->status === PurchaseOrderStatus::SUBMITTED, 422, 'Only submitted orders can be accepted.');

        $result = DB::transaction(function () use ($order, $storeId, $receivedItems, $user) {
            $order->update(['store_id' => $storeId]);

            $receivedMap = collect($receivedItems)->keyBy('id');

            foreach ($order->items as $item) {
                $received = $receivedMap->get($item->id);
                $receivedQty = $received ? (int) $received['received_quantity'] : $item->quantity;
                $correctionNote = $received['correction_note'] ?? null;

                $item->update([
                    'received_quantity' => $receivedQty,
                    'correction_note' => $correctionNote,
                ]);

                if ($receivedQty <= 0) {
                    continue;
                }

                // Add to destination store
                $this->adjustProductStore(
                    $item->product_id,
                    $storeId,
                    $receivedQty,
                    InventoryActivityCodes::PURCHASE_ORDER_IN,
                    $order->id,
                    $user
                );
            }

            $order->update([
                'status' => PurchaseOrderStatus::ACCEPTED,
                'resolved_at' => now(),
                'resolved_by' => $user->id,
            ]);

            return $order;
        });

        $this->sendNotification($result, 'Accepted');

        return $result;
    }

    public function reject(PurchaseOrder $order, string $reason, User $user): PurchaseOrder
    {
        abort_unless($order->status === PurchaseOrderStatus::SUBMITTED, 422, 'Only submitted orders can be rejected.');

        $order->update([
            'status' => PurchaseOrderStatus::REJECTED,
            'rejection_reason' => $reason,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);

        $this->sendNotification($order, 'Rejected');

        return $order;
    }

    public function revert(PurchaseOrder $order, User $user): PurchaseOrder
    {
        abort_unless($order->status === PurchaseOrderStatus::ACCEPTED, 422, 'Only accepted orders can be reverted.');

        $storeId = $order->store_id;

        $result = DB::transaction(function () use ($order, $user, $storeId) {
            foreach ($order->items as $item) {
                if ($item->received_quantity && $item->received_quantity > 0 && $storeId) {
                    // Subtract from destination store
                    $this->adjustProductStore(
                        $item->product_id,
                        $storeId,
                        -$item->received_quantity,
                        InventoryActivityCodes::PURCHASE_ORDER_REVERT_OUT,
                        $order->id,
                        $user
                    );
                }

                $item->update([
                    'received_quantity' => null,
                    'correction_note' => null,
                ]);
            }

            $order->update([
                'status' => PurchaseOrderStatus::SUBMITTED,
                'store_id' => null,
                'resolved_at' => null,
                'resolved_by' => null,
            ]);

            return $order;
        });

        $this->sendNotification($result, 'Reverted');

        return $result;
    }

    public function delete(PurchaseOrder $order): void
    {
        abort_unless($order->status === PurchaseOrderStatus::DRAFT, 422, 'Only draft orders can be deleted.');

        $order->delete();
    }

    protected function adjustProductStore(
        int $productId,
        int $storeId,
        int $qty,
        string $activityCode,
        int $purchaseOrderId,
        User $user
    ): InventoryLog {
        $productStore = ProductStore::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        $currentQty = $productStore?->quantity ?? 0;
        $newQty = $currentQty + $qty;

        if ($productStore) {
            $productStore->update(['quantity' => $newQty]);
        } else {
            ProductStore::create([
                'product_id' => $productId,
                'store_id' => $storeId,
                'quantity' => $newQty,
                'is_active' => true,
            ]);
        }

        return InventoryLog::create([
            'product_id' => $productId,
            'store_id' => $storeId,
            'purchase_order_id' => $purchaseOrderId,
            'activity_code' => $activityCode,
            'quantity_in' => $qty > 0 ? $qty : 0,
            'quantity_out' => $qty < 0 ? abs($qty) : 0,
            'current_quantity' => $newQty,
            'notes' => null,
            'created_by' => $user->id,
        ]);
    }

    protected function sendNotification(PurchaseOrder $order, string $action): void
    {
        $query = NotificationRecipient::forEventType(NotificationEventType::PurchaseOrder)
            ->active();

        // Filter by store when available, otherwise notify all recipients
        if ($order->store_id) {
            $query->where('store_id', $order->store_id);
        }

        $recipients = $query->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $order->loadMissing(['items.product', 'supplier', 'store', 'submittedByUser', 'resolvedByUser']);

        $mailable = new PurchaseOrderNotificationMail($order, $action);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(clone $mailable);
        }
    }
}
