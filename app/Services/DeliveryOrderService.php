<?php

namespace App\Services;

use App\Constants\InventoryActivityCodes;
use App\Enums\DeliveryOrderStatus;
use App\Enums\NotificationEventType;
use App\Mail\DeliveryOrderNotificationMail;
use App\Models\DeliveryOrder;
use App\Models\InventoryLog;
use App\Models\NotificationRecipient;
use App\Models\ProductStore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DeliveryOrderService
{
    public function create(array $data, User $user): DeliveryOrder
    {
        $order = DB::transaction(function () use ($data, $user) {
            $order = DeliveryOrder::create([
                'order_number' => DeliveryOrder::generateOrderNumber(),
                'store_id_from' => $data['store_id_from'],
                'store_id_to' => $data['store_id_to'],
                'status' => DeliveryOrderStatus::DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $order->load('items.product');
        });

        $this->sendNotification($order, 'Created');

        return $order;
    }

    public function update(DeliveryOrder $order, array $data, User $user): DeliveryOrder
    {
        abort_unless($order->status === DeliveryOrderStatus::DRAFT, 422, 'Only draft orders can be edited.');

        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'store_id_from' => $data['store_id_from'],
                'store_id_to' => $data['store_id_to'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Sync items: delete all and recreate
            $order->items()->delete();

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $order->load('items.product');
        });
    }

    public function submit(DeliveryOrder $order, User $user): DeliveryOrder
    {
        abort_unless($order->status === DeliveryOrderStatus::DRAFT, 422, 'Only draft orders can be submitted.');
        abort_unless($order->items()->count() > 0, 422, 'Cannot submit an order with no items.');

        $order->update([
            'status' => DeliveryOrderStatus::SUBMITTED,
            'submitted_at' => now(),
            'submitted_by' => $user->id,
        ]);

        $this->sendNotification($order, 'Submitted');

        return $order;
    }

    public function approve(DeliveryOrder $order, array $receivedItems, User $user): DeliveryOrder
    {
        abort_unless($order->status === DeliveryOrderStatus::SUBMITTED, 422, 'Only submitted orders can be approved.');

        $result = DB::transaction(function () use ($order, $receivedItems, $user) {
            // Index received items by item ID
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

                // Deduct from source store
                $this->adjustProductStore(
                    $item->product_id,
                    $order->store_id_from,
                    -$receivedQty,
                    InventoryActivityCodes::DELIVERY_ORDER_OUT,
                    $order->id,
                    null,
                    $user
                );

                // Add to destination store
                $this->adjustProductStore(
                    $item->product_id,
                    $order->store_id_to,
                    $receivedQty,
                    InventoryActivityCodes::DELIVERY_ORDER_IN,
                    $order->id,
                    null,
                    $user
                );
            }

            $order->update([
                'status' => DeliveryOrderStatus::APPROVED,
                'resolved_at' => now(),
                'resolved_by' => $user->id,
            ]);

            return $order;
        });

        $this->sendNotification($result, 'Approved');

        return $result;
    }

    public function reject(DeliveryOrder $order, string $reason, User $user): DeliveryOrder
    {
        abort_unless($order->status === DeliveryOrderStatus::SUBMITTED, 422, 'Only submitted orders can be rejected.');

        $order->update([
            'status' => DeliveryOrderStatus::REJECTED,
            'rejection_reason' => $reason,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);

        $this->sendNotification($order, 'Rejected');

        return $order;
    }

    public function revert(DeliveryOrder $order, User $user): DeliveryOrder
    {
        abort_unless($order->status === DeliveryOrderStatus::APPROVED, 422, 'Only approved orders can be reverted.');

        $result = DB::transaction(function () use ($order, $user) {
            foreach ($order->items as $item) {
                if ($item->received_quantity && $item->received_quantity > 0) {
                    // Add back to source store
                    $this->adjustProductStore(
                        $item->product_id,
                        $order->store_id_from,
                        $item->received_quantity,
                        InventoryActivityCodes::DELIVERY_ORDER_REVERT_IN,
                        $order->id,
                        null,
                        $user
                    );

                    // Subtract from destination store
                    $this->adjustProductStore(
                        $item->product_id,
                        $order->store_id_to,
                        -$item->received_quantity,
                        InventoryActivityCodes::DELIVERY_ORDER_REVERT_OUT,
                        $order->id,
                        null,
                        $user
                    );
                }

                $item->update([
                    'received_quantity' => null,
                    'correction_note' => null,
                ]);
            }

            $order->update([
                'status' => DeliveryOrderStatus::SUBMITTED,
                'resolved_at' => null,
                'resolved_by' => null,
            ]);

            return $order;
        });

        $this->sendNotification($result, 'Reverted');

        return $result;
    }

    public function delete(DeliveryOrder $order): void
    {
        abort_unless($order->status === DeliveryOrderStatus::DRAFT, 422, 'Only draft orders can be deleted.');

        $order->delete();
    }

    protected function adjustProductStore(
        int $productId,
        int $storeId,
        int $adjustQty,
        string $activityCode,
        ?int $deliveryOrderId,
        ?int $purchaseOrderId,
        User $user
    ): InventoryLog {
        $productStore = ProductStore::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        $currentQty = $productStore?->quantity ?? 0;
        $newQty = $currentQty + $adjustQty;

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
            'delivery_order_id' => $deliveryOrderId,
            'purchase_order_id' => $purchaseOrderId,
            'activity_code' => $activityCode,
            'quantity_in' => $adjustQty > 0 ? $adjustQty : 0,
            'quantity_out' => $adjustQty < 0 ? abs($adjustQty) : 0,
            'current_quantity' => $newQty,
            'notes' => null,
            'created_by' => $user->id,
        ]);
    }

    protected function sendNotification(DeliveryOrder $order, string $action): void
    {
        // Delivery orders involve two stores - notify recipients of both
        $recipients = NotificationRecipient::forEventType(NotificationEventType::DeliveryOrder)
            ->whereIn('store_id', [$order->store_id_from, $order->store_id_to])
            ->active()
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $order->loadMissing(['items.product', 'storeFrom', 'storeTo', 'submittedByUser', 'resolvedByUser']);

        $mailable = new DeliveryOrderNotificationMail($order, $action);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(clone $mailable);
        }
    }
}
