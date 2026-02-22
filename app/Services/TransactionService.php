<?php

namespace App\Services;

use App\Constants\InventoryActivityCodes;
use App\Enums\NotificationEventType;
use App\Enums\TransactionChangeType;
use App\Enums\TransactionStatus;
use App\Mail\TransactionAmendedMail;
use App\Mail\TransactionCompletedMail;
use App\Mail\TransactionRefundMail;
use App\Models\Customer;
use App\Models\InventoryLog;
use App\Models\NotificationRecipient;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TransactionService
{
    public function __construct(
        protected OfferService $offerService
    ) {}

    /**
     * Create a new draft transaction.
     */
    public function create(int $storeId, int $employeeId, int $currencyId, int $userId): Transaction
    {
        return DB::transaction(function () use ($storeId, $employeeId, $currencyId, $userId) {
            $store = Store::findOrFail($storeId);

            $transaction = Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber($store),
                'store_id' => $storeId,
                'employee_id' => $employeeId,
                'currency_id' => $currencyId,
                'status' => TransactionStatus::DRAFT,
                'tax_percentage' => $store->tax_percentage,
                'tax_inclusive' => $store->include_tax ?? false,
                'created_by' => $userId,
            ]);

            $this->createVersion(
                $transaction,
                TransactionChangeType::CREATED,
                $userId,
                'Transaction created'
            );

            return $transaction->load('store', 'employee', 'currency', 'items', 'payments');
        });
    }

    /**
     * Add an item to the transaction.
     */
    public function addItem(Transaction $transaction, int $productId, int $quantity, int $userId): Transaction
    {
        $this->assertEditable($transaction);

        return DB::transaction(function () use ($transaction, $productId, $quantity, $userId) {
            $product = Product::findOrFail($productId);
            $priceData = $this->resolvePrice($product, $transaction->store_id, $transaction->currency_id);

            if ($priceData['unit_price'] === null) {
                abort(422, "No price found for product {$product->product_name} in this store/currency.");
            }

            // Check if item already exists (same product) - increment quantity
            $existingItem = $transaction->items()->where('product_id', $productId)->first();
            if ($existingItem) {
                return $this->updateItem($transaction, $existingItem->id, [
                    'quantity' => $existingItem->quantity + $quantity,
                ], $userId);
            }

            $unitPrice = $priceData['unit_price'];

            // Resolve best product-level offer
            $offer = $this->offerService->findBestProductOffer(
                $product,
                $transaction->store_id,
                $transaction->currency_id,
                $unitPrice
            );

            // Customer discount
            $customerDiscountPercentage = null;
            $customerDiscountAmount = '0';
            if ($transaction->customer_id && $transaction->customer) {
                $customerDiscountPercentage = $transaction->customer->discount_percentage;
                if ($customerDiscountPercentage) {
                    $customerDiscountAmount = bcmul(
                        $unitPrice,
                        bcdiv((string) $customerDiscountPercentage, '100', 6),
                        4
                    );
                }
            }

            // Calculate line amounts
            $lineSubtotal = bcmul($unitPrice, (string) $quantity, 4);
            $lineDiscount = $this->calculateLineDiscount(
                $unitPrice,
                $quantity,
                $offer,
                $customerDiscountPercentage,
                $lineSubtotal
            );
            $lineTotal = bcsub($lineSubtotal, $lineDiscount, 4);

            $nextSort = ($transaction->items()->max('sort_order') ?? -1) + 1;

            $transaction->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'product_number' => $product->product_number,
                'variant_name' => $product->variant_name,
                'barcode' => $product->barcode,
                'quantity' => $quantity,
                'cost_price' => $priceData['cost_price'],
                'unit_price' => $unitPrice,
                'offer_id' => $offer['offer_id'] ?? null,
                'offer_name' => $offer['offer_name'] ?? null,
                'offer_discount_type' => $offer['discount_type'] ?? null,
                'offer_discount_amount' => $offer['discount_amount'] ?? '0',
                'offer_is_combinable' => $offer['is_combinable'] ?? null,
                'customer_discount_percentage' => $customerDiscountPercentage,
                'customer_discount_amount' => $customerDiscountAmount !== '0' ? $customerDiscountAmount : '0',
                'line_subtotal' => $lineSubtotal,
                'line_discount' => $lineDiscount,
                'line_total' => $lineTotal,
                'sort_order' => $nextSort,
            ]);

            $transaction->refresh();
            $this->recalculateTotals($transaction);

            // If completed transaction, decrement inventory for new item
            if ($transaction->status === TransactionStatus::COMPLETED) {
                $newItem = $transaction->items()->where('product_id', $product->id)->first();
                if ($newItem) {
                    $this->decrementInventory($transaction, $newItem, $userId);
                }
            }

            $this->createVersion(
                $transaction,
                TransactionChangeType::ITEM_ADDED,
                $userId,
                "Added {$product->product_name}" . ($product->variant_name ? " ({$product->variant_name})" : '') . " x{$quantity}"
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Update an existing item (quantity, price, etc.).
     */
    public function updateItem(Transaction $transaction, int $itemId, array $data, int $userId): Transaction
    {
        $this->assertEditable($transaction);

        return DB::transaction(function () use ($transaction, $itemId, $data, $userId) {
            $item = $transaction->items()->findOrFail($itemId);
            $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;

            $oldQuantity = $item->quantity;
            $oldUnitPrice = (string) $item->unit_price;
            $quantity = $data['quantity'] ?? $item->quantity;
            $unitPrice = $data['unit_price'] ?? (string) $item->unit_price;

            // Re-resolve offer if price changed
            $offer = null;
            if (isset($data['unit_price']) && $data['unit_price'] !== (string) $item->unit_price) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $offer = $this->offerService->findBestProductOffer(
                        $product,
                        $transaction->store_id,
                        $transaction->currency_id,
                        $unitPrice
                    );
                }
            }

            // Customer discount
            $customerDiscountPercentage = $item->customer_discount_percentage;
            if ($transaction->customer_id && $transaction->customer) {
                $customerDiscountPercentage = $transaction->customer->discount_percentage;
            }

            $lineSubtotal = bcmul($unitPrice, (string) $quantity, 4);

            // Use existing or new offer data
            $offerData = $offer ?? [
                'offer_id' => $item->offer_id,
                'offer_name' => $item->offer_name,
                'discount_type' => $item->offer_discount_type,
                'discount_amount' => (string) $item->offer_discount_amount,
                'is_combinable' => $item->offer_is_combinable,
            ];

            $lineDiscount = $this->calculateLineDiscount(
                $unitPrice,
                $quantity,
                $offerData,
                $customerDiscountPercentage,
                $lineSubtotal
            );
            $lineTotal = bcsub($lineSubtotal, $lineDiscount, 4);

            $item->update([
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'offer_id' => $offerData['offer_id'] ?? null,
                'offer_name' => $offerData['offer_name'] ?? null,
                'offer_discount_type' => $offerData['discount_type'] ?? null,
                'offer_discount_amount' => $offerData['discount_amount'] ?? '0',
                'offer_is_combinable' => $offerData['is_combinable'] ?? null,
                'customer_discount_percentage' => $customerDiscountPercentage,
                'line_subtotal' => $lineSubtotal,
                'line_discount' => $lineDiscount,
                'line_total' => $lineTotal,
            ]);

            $transaction->refresh();
            $this->recalculateTotals($transaction);

            // If completed transaction, adjust inventory
            if ($wasCompleted && $quantity !== $oldQuantity) {
                $this->adjustInventoryForItemChange($transaction, $item, $oldQuantity, $quantity, $userId);
            }

            $changes = [];
            if ($quantity !== $oldQuantity) {
                $changes[] = "qty {$oldQuantity} → {$quantity}";
            }
            if (bccomp($unitPrice, $oldUnitPrice, 4) !== 0) {
                $changes[] = "price {$oldUnitPrice} → {$unitPrice}";
            }
            $changeSummary = $changes
                ? "Modified {$item->product_name}: " . implode(', ', $changes)
                : "Modified {$item->product_name}";

            $this->createVersion(
                $transaction,
                TransactionChangeType::ITEM_MODIFIED,
                $userId,
                $changeSummary
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Remove an item from the transaction.
     */
    public function removeItem(Transaction $transaction, int $itemId, int $userId): Transaction
    {
        $this->assertEditable($transaction);

        return DB::transaction(function () use ($transaction, $itemId, $userId) {
            $item = $transaction->items()->findOrFail($itemId);
            $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;

            // If completed, restore inventory
            if ($wasCompleted) {
                $this->adjustInventoryForItemChange($transaction, $item, $item->quantity, 0, $userId);
            }

            $productName = $item->product_name;
            $item->delete();

            $transaction->refresh();
            $this->recalculateTotals($transaction);

            $this->createVersion(
                $transaction,
                TransactionChangeType::ITEM_REMOVED,
                $userId,
                "Removed {$productName}"
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Set or clear the customer on a transaction.
     */
    public function setCustomer(Transaction $transaction, ?int $customerId, int $userId): Transaction
    {
        $this->assertEditable($transaction);

        return DB::transaction(function () use ($transaction, $customerId, $userId) {
            $customer = $customerId ? Customer::findOrFail($customerId) : null;
            $oldCustomerName = $transaction->customer?->first_name ?? 'None';
            $newCustomerName = $customer?->first_name ?? 'None';

            $transaction->update([
                'customer_id' => $customerId,
                'customer_discount_percentage' => $customer?->discount_percentage,
            ]);

            // Recalculate all items with new customer discount
            $transaction->refresh();
            $this->recalculateItemDiscounts($transaction);
            $this->recalculateTotals($transaction);

            $this->createVersion(
                $transaction,
                TransactionChangeType::CUSTOMER_CHANGED,
                $userId,
                "Customer: {$oldCustomerName} → {$newCustomerName}"
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Clear the customer discount without removing the customer.
     */
    public function clearCustomerDiscount(Transaction $transaction, int $userId): Transaction
    {
        $this->assertEditable($transaction);

        return DB::transaction(function () use ($transaction, $userId) {
            $transaction->update([
                'customer_discount_percentage' => null,
            ]);

            $transaction->refresh();
            $this->recalculateItemDiscounts($transaction);
            $this->recalculateTotals($transaction);

            $this->createVersion(
                $transaction,
                TransactionChangeType::DISCOUNT_APPLIED,
                $userId,
                'Customer discount removed'
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Restore the customer discount (re-read from the assigned customer).
     */
    public function restoreCustomerDiscount(Transaction $transaction, int $userId): Transaction
    {
        $this->assertEditable($transaction);

        abort_unless(
            $transaction->customer_id && $transaction->customer,
            422,
            'No customer assigned to this transaction.'
        );

        return DB::transaction(function () use ($transaction, $userId) {
            $discountPercentage = $transaction->customer->discount_percentage;

            $transaction->update([
                'customer_discount_percentage' => $discountPercentage,
            ]);

            $transaction->refresh();
            $this->recalculateItemDiscounts($transaction);
            $this->recalculateTotals($transaction);

            $this->createVersion(
                $transaction,
                TransactionChangeType::DISCOUNT_APPLIED,
                $userId,
                'Customer discount restored'
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Apply a manual discount to the transaction.
     */
    public function applyManualDiscount(Transaction $transaction, string $type, string $value, int $userId): Transaction
    {
        $this->assertEditable($transaction);

        return DB::transaction(function () use ($transaction, $type, $value, $userId) {
            $transaction->loadMissing('items');

            // Calculate after-line-discounts subtotal
            $afterLineDiscounts = '0';
            foreach ($transaction->items as $item) {
                if (! $item->is_refunded) {
                    $afterLineDiscounts = bcadd($afterLineDiscounts, (string) $item->line_total, 4);
                }
            }

            if ($type === 'percentage') {
                $discountAmount = bcmul(
                    $afterLineDiscounts,
                    bcdiv($value, '100', 6),
                    4
                );
            } else {
                $discountAmount = $value;
            }

            // Clamp so total doesn't go negative
            if (bccomp($discountAmount, $afterLineDiscounts, 4) > 0) {
                $discountAmount = $afterLineDiscounts;
            }

            $transaction->update([
                'manual_discount' => $discountAmount,
                'manual_discount_type' => $type,
                'manual_discount_value' => $value,
            ]);

            $transaction->refresh();
            $this->recalculateTotals($transaction);

            $label = $type === 'percentage' ? "{$value}%" : $value;
            $this->createVersion(
                $transaction,
                TransactionChangeType::DISCOUNT_APPLIED,
                $userId,
                "Manual discount applied: {$label}"
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Clear the manual discount from the transaction.
     */
    public function clearManualDiscount(Transaction $transaction, int $userId): Transaction
    {
        $this->assertEditable($transaction);

        return DB::transaction(function () use ($transaction, $userId) {
            $transaction->update([
                'manual_discount' => '0',
                'manual_discount_type' => null,
                'manual_discount_value' => null,
            ]);

            $transaction->refresh();
            $this->recalculateTotals($transaction);

            $this->createVersion(
                $transaction,
                TransactionChangeType::DISCOUNT_APPLIED,
                $userId,
                'Manual discount removed'
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Add a payment to the transaction.
     */
    public function addPayment(Transaction $transaction, int $paymentModeId, string $amount, ?array $paymentData, int $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $paymentModeId, $amount, $paymentData, $userId) {
            $paymentMode = PaymentMode::findOrFail($paymentModeId);

            $currentPaid = (string) $transaction->amount_paid;
            $newPaid = bcadd($currentPaid, $amount, 4);
            $balanceAfter = bcsub((string) $transaction->total, $newPaid, 4);

            $nextRow = ($transaction->payments()->max('row_number') ?? 0) + 1;

            $transaction->payments()->create([
                'payment_mode_id' => $paymentModeId,
                'payment_mode_name' => $paymentMode->name,
                'amount' => $amount,
                'payment_data' => $paymentData,
                'row_number' => $nextRow,
                'balance_after' => $balanceAfter,
                'created_by' => $userId,
            ]);

            $transaction->update([
                'amount_paid' => $newPaid,
                'balance_due' => bccomp($balanceAfter, '0', 4) > 0 ? $balanceAfter : '0',
                'change_amount' => bccomp($balanceAfter, '0', 4) < 0 ? bcmul($balanceAfter, '-1', 4) : '0',
            ]);

            $transaction->refresh();

            $this->createVersion(
                $transaction,
                TransactionChangeType::PAYMENT_ADDED,
                $userId,
                "Payment: {$paymentMode->name} {$amount}"
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Remove a payment from the transaction.
     */
    public function removePayment(Transaction $transaction, int $paymentId, int $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $paymentId, $userId) {
            $payment = $transaction->payments()->findOrFail($paymentId);
            $paymentName = $payment->payment_mode_name;
            $paymentAmount = (string) $payment->amount;

            $payment->delete();

            // Recalculate payment totals
            $newPaid = bcsub((string) $transaction->amount_paid, $paymentAmount, 4);
            if (bccomp($newPaid, '0', 4) < 0) {
                $newPaid = '0';
            }
            $balanceDue = bcsub((string) $transaction->total, $newPaid, 4);

            $transaction->update([
                'amount_paid' => $newPaid,
                'balance_due' => bccomp($balanceDue, '0', 4) > 0 ? $balanceDue : '0',
                'change_amount' => bccomp($balanceDue, '0', 4) < 0 ? bcmul($balanceDue, '-1', 4) : '0',
            ]);

            $transaction->refresh();

            $this->createVersion(
                $transaction,
                TransactionChangeType::PAYMENT_REMOVED,
                $userId,
                "Removed payment: {$paymentName} {$paymentAmount}"
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Complete (finalize) a transaction.
     */
    public function complete(Transaction $transaction, int $userId): Transaction
    {
        abort_unless(
            $transaction->status === TransactionStatus::DRAFT,
            422,
            'Only draft transactions can be completed.'
        );

        abort_unless(
            $transaction->items()->count() > 0,
            422,
            'Cannot complete a transaction with no items.'
        );

        $balanceDue = (string) $transaction->balance_due;
        abort_unless(
            bccomp($balanceDue, '0', 4) <= 0,
            422,
            'Cannot complete transaction with outstanding balance.'
        );

        $result = DB::transaction(function () use ($transaction, $userId) {
            $transaction->update([
                'status' => TransactionStatus::COMPLETED,
                'checkout_date' => now(),
            ]);

            // Decrement inventory for each item
            foreach ($transaction->items as $item) {
                $this->decrementInventory($transaction, $item, $userId);
            }

            $transaction->refresh();

            $this->createVersion(
                $transaction,
                TransactionChangeType::COMPLETED,
                $userId,
                'Transaction completed'
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });

        $this->sendTransactionNotification($result, NotificationEventType::Transaction, new TransactionCompletedMail($result));

        return $result;
    }

    /**
     * Suspend (park) a transaction.
     */
    public function suspend(Transaction $transaction, int $userId): Transaction
    {
        abort_unless(
            $transaction->status === TransactionStatus::DRAFT,
            422,
            'Only draft transactions can be suspended.'
        );

        return DB::transaction(function () use ($transaction, $userId) {
            $transaction->update(['status' => TransactionStatus::SUSPENDED]);

            $this->createVersion(
                $transaction,
                TransactionChangeType::SUSPENDED,
                $userId,
                'Transaction suspended'
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Resume a suspended transaction.
     */
    public function resume(Transaction $transaction, int $userId): Transaction
    {
        abort_unless(
            $transaction->status === TransactionStatus::SUSPENDED,
            422,
            'Only suspended transactions can be resumed.'
        );

        return DB::transaction(function () use ($transaction, $userId) {
            $transaction->update(['status' => TransactionStatus::DRAFT]);

            $this->createVersion(
                $transaction,
                TransactionChangeType::RESUMED,
                $userId,
                'Transaction resumed'
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });
    }

    /**
     * Void a transaction and restore inventory.
     */
    public function void(Transaction $transaction, int $userId, ?string $reason = null): Transaction
    {
        abort_unless(
            in_array($transaction->status, [TransactionStatus::COMPLETED, TransactionStatus::DRAFT, TransactionStatus::SUSPENDED]),
            422,
            'This transaction cannot be voided.'
        );

        $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;

        $result = DB::transaction(function () use ($transaction, $userId, $reason, $wasCompleted) {
            $transaction->update([
                'status' => TransactionStatus::VOIDED,
                'comments' => $reason ? ($transaction->comments ? $transaction->comments . "\n" . $reason : $reason) : $transaction->comments,
            ]);

            // Restore inventory if was completed
            if ($wasCompleted) {
                foreach ($transaction->items as $item) {
                    if (! $item->is_refunded) {
                        $this->restoreInventory($transaction, $item, $item->quantity, $userId);
                    }
                }
            }

            $transaction->refresh();

            $this->createVersion(
                $transaction,
                TransactionChangeType::VOIDED,
                $userId,
                'Transaction voided' . ($reason ? ": {$reason}" : '')
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });

        if ($wasCompleted) {
            $changeSummary = 'Transaction voided' . ($reason ? ": {$reason}" : '');
            $this->sendTransactionNotification($result, NotificationEventType::AmendedTransaction, new TransactionAmendedMail($result, $changeSummary));
        }

        return $result;
    }

    /**
     * Process a refund for selected items.
     *
     * @param  array  $refundItems  [{item_id, quantity, reason}]
     */
    public function processRefund(Transaction $transaction, array $refundItems, int $userId): Transaction
    {
        abort_unless(
            $transaction->status === TransactionStatus::COMPLETED,
            422,
            'Only completed transactions can be refunded.'
        );

        $refundSummaryText = '';

        $result = DB::transaction(function () use ($transaction, $refundItems, $userId, &$refundSummaryText) {
            $refundSummary = [];

            foreach ($refundItems as $refundData) {
                $item = $transaction->items()->findOrFail($refundData['item_id']);

                abort_if($item->is_refunded, 422, "Item '{$item->product_name}' is already refunded.");

                $refundQty = $refundData['quantity'] ?? $item->quantity;
                abort_if($refundQty > $item->quantity, 422, "Refund quantity exceeds item quantity.");

                if ($refundQty === $item->quantity) {
                    // Full item refund
                    $item->update([
                        'is_refunded' => true,
                        'refund_reason' => $refundData['reason'] ?? null,
                    ]);
                } else {
                    // Partial refund - reduce quantity
                    $newQuantity = $item->quantity - $refundQty;
                    $lineSubtotal = bcmul((string) $item->unit_price, (string) $newQuantity, 4);

                    $lineDiscount = $this->calculateLineDiscount(
                        (string) $item->unit_price,
                        $newQuantity,
                        [
                            'offer_id' => $item->offer_id,
                            'offer_name' => $item->offer_name,
                            'discount_type' => $item->offer_discount_type,
                            'discount_amount' => (string) $item->offer_discount_amount,
                            'is_combinable' => $item->offer_is_combinable,
                        ],
                        $item->customer_discount_percentage,
                        $lineSubtotal
                    );

                    $lineTotal = bcsub($lineSubtotal, $lineDiscount, 4);

                    $item->update([
                        'quantity' => $newQuantity,
                        'line_subtotal' => $lineSubtotal,
                        'line_discount' => $lineDiscount,
                        'line_total' => $lineTotal,
                    ]);
                }

                // Restore inventory
                $this->restoreInventory($transaction, $item, $refundQty, $userId);
                $refundSummary[] = "{$item->product_name} x{$refundQty}";
            }

            $refundSummaryText = 'Refund: ' . implode(', ', $refundSummary);

            // Recalculate totals
            $transaction->refresh();
            $oldTotal = (string) $transaction->total;
            $this->recalculateTotals($transaction);
            $transaction->refresh();
            $newTotal = (string) $transaction->total;

            $refundAmount = bcsub($oldTotal, $newTotal, 4);
            if (bccomp($refundAmount, '0', 4) < 0) {
                $refundAmount = '0';
            }

            $transaction->update([
                'refund_amount' => bcadd((string) $transaction->refund_amount, $refundAmount, 4),
            ]);

            $transaction->refresh();

            $this->createVersion(
                $transaction,
                TransactionChangeType::REFUND,
                $userId,
                $refundSummaryText
            );

            return $transaction->load('items.product', 'payments', 'customer', 'currency', 'store', 'employee');
        });

        $this->sendTransactionNotification($result, NotificationEventType::Refund, new TransactionRefundMail($result, $refundSummaryText));

        return $result;
    }

    /**
     * Master recalculation of all transaction totals.
     */
    public function recalculateTotals(Transaction $transaction): void
    {
        $transaction->loadMissing('items');

        // Sum line totals (only non-refunded items)
        $subtotal = '0';
        $offerDiscount = '0';
        $customerDiscount = '0';

        foreach ($transaction->items as $item) {
            if ($item->is_refunded) {
                continue;
            }
            $subtotal = bcadd($subtotal, (string) $item->line_subtotal, 4);
            $offerDiscount = bcadd($offerDiscount, bcmul((string) $item->offer_discount_amount, (string) $item->quantity, 4), 4);
            $customerDiscount = bcadd($customerDiscount, bcmul((string) $item->customer_discount_amount, (string) $item->quantity, 4), 4);
        }

        // Resolve bundle & min-spend offers
        $bundleDiscount = '0';
        $bundleOfferId = null;
        $bundleOfferName = null;
        $minSpendDiscount = '0';
        $minSpendOfferId = null;
        $minSpendOfferName = null;

        $cartItems = $this->buildCartItemsArray($transaction);
        $afterLineDiscounts = '0';
        foreach ($transaction->items as $item) {
            if (! $item->is_refunded) {
                $afterLineDiscounts = bcadd($afterLineDiscounts, (string) $item->line_total, 4);
            }
        }

        // Bundle offers
        if (! empty($cartItems)) {
            $bundles = $this->offerService->findApplicableBundleOffers(
                $cartItems,
                $transaction->store_id,
                $transaction->currency_id
            );
            if (! empty($bundles)) {
                $bestBundle = $bundles[0];
                $bundleDiscount = $this->calculateBundleDiscount($bestBundle, $afterLineDiscounts);
                $bundleOfferId = $bestBundle['offer_id'];
                $bundleOfferName = $bestBundle['offer_name'];
            }
        }

        // Minimum spend offers (check against subtotal after line-item discounts)
        if (bccomp($afterLineDiscounts, '0', 4) > 0) {
            $minSpend = $this->offerService->findBestMinimumSpendOffer(
                $afterLineDiscounts,
                $transaction->store_id,
                $transaction->currency_id
            );
            if ($minSpend) {
                $minSpendDiscount = $minSpend['discount_amount'];
                $minSpendOfferId = $minSpend['offer_id'];
                $minSpendOfferName = $minSpend['offer_name'];
            }
        }

        $manualDiscount = (string) $transaction->manual_discount;

        // Calculate total after all discounts
        $afterDiscounts = $afterLineDiscounts;
        $afterDiscounts = bcsub($afterDiscounts, $bundleDiscount, 4);
        $afterDiscounts = bcsub($afterDiscounts, $minSpendDiscount, 4);
        $afterDiscounts = bcsub($afterDiscounts, $manualDiscount, 4);

        if (bccomp($afterDiscounts, '0', 4) < 0) {
            $afterDiscounts = '0';
        }

        // Tax calculation
        $taxAmount = '0';
        $total = $afterDiscounts;

        if ($transaction->tax_percentage && bccomp((string) $transaction->tax_percentage, '0', 2) > 0) {
            $taxRate = bcdiv((string) $transaction->tax_percentage, '100', 6);
            if ($transaction->tax_inclusive) {
                $taxAmount = bcsub(
                    $afterDiscounts,
                    bcdiv($afterDiscounts, bcadd('1', $taxRate, 6), 4),
                    4
                );
                $total = $afterDiscounts;
            } else {
                $taxAmount = bcmul($afterDiscounts, $taxRate, 4);
                $total = bcadd($afterDiscounts, $taxAmount, 4);
            }
        }

        // Payment balances
        $amountPaid = (string) $transaction->amount_paid;
        $balanceDue = bcsub($total, $amountPaid, 4);
        $changeAmount = '0';
        if (bccomp($balanceDue, '0', 4) < 0) {
            $changeAmount = bcmul($balanceDue, '-1', 4);
            $balanceDue = '0';
        }

        $transaction->update([
            'subtotal' => $subtotal,
            'offer_discount' => $offerDiscount,
            'bundle_discount' => $bundleDiscount,
            'minimum_spend_discount' => $minSpendDiscount,
            'customer_discount' => $customerDiscount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'balance_due' => $balanceDue,
            'change_amount' => $changeAmount,
            'bundle_offer_id' => $bundleOfferId,
            'bundle_offer_name' => $bundleOfferName,
            'minimum_spend_offer_id' => $minSpendOfferId,
            'minimum_spend_offer_name' => $minSpendOfferName,
        ]);
    }

    /**
     * Resolve price for a product at a store in a given currency.
     * Store price → Base price → null.
     */
    public function resolvePrice(Product $product, int $storeId, int $currencyId): array
    {
        $productStore = ProductStore::where('product_id', $product->id)
            ->where('store_id', $storeId)
            ->with('storePrices')
            ->first();

        $unitPrice = null;
        $costPrice = null;

        if ($productStore) {
            $unitPrice = $productStore->getEffectiveUnitPrice($currencyId);
            $costPrice = $productStore->getEffectiveCostPrice($currencyId);
        }

        // Fallback to base product price
        if ($unitPrice === null) {
            $basePrice = $product->getPriceForCurrency($currencyId);
            if ($basePrice) {
                $unitPrice = $basePrice->unit_price;
                $costPrice = $costPrice ?? $basePrice->cost_price;
            }
        }

        return [
            'unit_price' => $unitPrice,
            'cost_price' => $costPrice,
        ];
    }

    // ──── Private Helpers ────

    protected function assertEditable(Transaction $transaction): void
    {
        abort_unless(
            in_array($transaction->status, [TransactionStatus::DRAFT, TransactionStatus::COMPLETED]),
            422,
            'This transaction cannot be edited.'
        );
    }

    /**
     * Calculate the line discount for a single item considering offer + customer discount stacking.
     */
    protected function calculateLineDiscount(
        string $unitPrice,
        int $quantity,
        ?array $offer,
        ?string $customerDiscountPercentage,
        string $lineSubtotal
    ): string {
        $offerDiscount = '0';
        $offerDiscountAmount = $offer['discount_amount'] ?? '0';
        if ($offerDiscountAmount && bccomp($offerDiscountAmount, '0', 4) > 0) {
            $offerDiscount = bcmul($offerDiscountAmount, (string) $quantity, 4);
        }

        $customerDiscountAmount = '0';
        if ($customerDiscountPercentage && bccomp((string) $customerDiscountPercentage, '0', 2) > 0) {
            $customerDiscountAmount = bcmul(
                $lineSubtotal,
                bcdiv((string) $customerDiscountPercentage, '100', 6),
                4
            );
        }

        // Determine effective discount based on combinability
        $lineDiscount = '0';
        $isCombinable = $offer['is_combinable'] ?? false;

        if (bccomp($offerDiscount, '0', 4) > 0 && bccomp($customerDiscountAmount, '0', 4) > 0) {
            if ($isCombinable) {
                $lineDiscount = bcadd($offerDiscount, $customerDiscountAmount, 4);
            } else {
                $lineDiscount = bccomp($offerDiscount, $customerDiscountAmount, 4) >= 0
                    ? $offerDiscount
                    : $customerDiscountAmount;
            }
        } elseif (bccomp($offerDiscount, '0', 4) > 0) {
            $lineDiscount = $offerDiscount;
        } elseif (bccomp($customerDiscountAmount, '0', 4) > 0) {
            $lineDiscount = $customerDiscountAmount;
        }

        // Don't exceed subtotal
        if (bccomp($lineDiscount, $lineSubtotal, 4) > 0) {
            $lineDiscount = $lineSubtotal;
        }

        return $lineDiscount;
    }

    /**
     * Recalculate discounts for all items (e.g., when customer changes).
     */
    protected function recalculateItemDiscounts(Transaction $transaction): void
    {
        $customerDiscountPercentage = $transaction->customer_discount_percentage;

        foreach ($transaction->items as $item) {
            if ($item->is_refunded) {
                continue;
            }

            $unitPrice = (string) $item->unit_price;
            $quantity = $item->quantity;
            $lineSubtotal = bcmul($unitPrice, (string) $quantity, 4);

            $customerDiscountAmount = '0';
            if ($customerDiscountPercentage && bccomp((string) $customerDiscountPercentage, '0', 2) > 0) {
                $customerDiscountAmount = bcmul(
                    $unitPrice,
                    bcdiv((string) $customerDiscountPercentage, '100', 6),
                    4
                );
            }

            $lineDiscount = $this->calculateLineDiscount(
                $unitPrice,
                $quantity,
                [
                    'offer_id' => $item->offer_id,
                    'offer_name' => $item->offer_name,
                    'discount_type' => $item->offer_discount_type,
                    'discount_amount' => (string) $item->offer_discount_amount,
                    'is_combinable' => $item->offer_is_combinable,
                ],
                $customerDiscountPercentage ? (string) $customerDiscountPercentage : null,
                $lineSubtotal
            );

            $lineTotal = bcsub($lineSubtotal, $lineDiscount, 4);

            $item->update([
                'customer_discount_percentage' => $customerDiscountPercentage,
                'customer_discount_amount' => $customerDiscountAmount,
                'line_subtotal' => $lineSubtotal,
                'line_discount' => $lineDiscount,
                'line_total' => $lineTotal,
            ]);
        }
    }

    /**
     * Build cart items array for bundle offer resolution.
     */
    protected function buildCartItemsArray(Transaction $transaction): array
    {
        $cartItems = [];
        foreach ($transaction->items as $item) {
            if ($item->is_refunded) {
                continue;
            }
            $product = $item->product ?? Product::find($item->product_id);
            $cartItems[$item->product_id] = [
                'quantity' => $item->quantity,
                'category_id' => $product?->category_id,
                'subcategory_id' => $product?->subcategory_id,
            ];
        }

        return $cartItems;
    }

    /**
     * Calculate bundle discount amount.
     */
    protected function calculateBundleDiscount(array $bundleOffer, string $cartTotal): string
    {
        if ($bundleOffer['discount_type'] === 'percentage' && $bundleOffer['discount_percentage']) {
            $discount = bcmul($cartTotal, bcdiv((string) $bundleOffer['discount_percentage'], '100', 6), 4);
            $amount = $bundleOffer['amount'] ?? null;
            if ($amount && isset($amount->max_discount_amount) && $amount->max_discount_amount) {
                if (bccomp($discount, (string) $amount->max_discount_amount, 4) > 0) {
                    $discount = (string) $amount->max_discount_amount;
                }
            }

            return $discount;
        }

        if ($bundleOffer['discount_type'] === 'fixed') {
            $amount = $bundleOffer['amount'] ?? null;
            if ($amount && isset($amount->discount_amount) && $amount->discount_amount) {
                $discount = (string) $amount->discount_amount;
                if (bccomp($discount, $cartTotal, 4) > 0) {
                    return $cartTotal;
                }

                return $discount;
            }
        }

        return '0';
    }

    /**
     * Decrement inventory when a sale is completed.
     */
    protected function decrementInventory(Transaction $transaction, TransactionItem $item, int $userId): void
    {
        $productStore = ProductStore::where('product_id', $item->product_id)
            ->where('store_id', $transaction->store_id)
            ->first();

        if ($productStore) {
            $productStore->decrement('quantity', $item->quantity);
            $productStore->refresh();

            InventoryLog::create([
                'product_id' => $item->product_id,
                'store_id' => $transaction->store_id,
                'transaction_id' => $transaction->id,
                'activity_code' => InventoryActivityCodes::SOLD_ITEM,
                'quantity_out' => $item->quantity,
                'current_quantity' => $productStore->quantity,
                'notes' => "Sale: {$transaction->transaction_number}",
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * Restore inventory when items are refunded or transaction is voided.
     */
    protected function restoreInventory(Transaction $transaction, TransactionItem $item, int $quantity, int $userId): void
    {
        $productStore = ProductStore::where('product_id', $item->product_id)
            ->where('store_id', $transaction->store_id)
            ->first();

        if ($productStore) {
            $productStore->increment('quantity', $quantity);
            $productStore->refresh();

            InventoryLog::create([
                'product_id' => $item->product_id,
                'store_id' => $transaction->store_id,
                'transaction_id' => $transaction->id,
                'activity_code' => InventoryActivityCodes::REFUND_ITEM,
                'quantity_in' => $quantity,
                'current_quantity' => $productStore->quantity,
                'notes' => "Refund: {$transaction->transaction_number}",
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * Adjust inventory when an item's quantity changes on a completed transaction.
     */
    protected function adjustInventoryForItemChange(Transaction $transaction, TransactionItem $item, int $oldQty, int $newQty, int $userId): void
    {
        $diff = $newQty - $oldQty;
        if ($diff === 0) {
            return;
        }

        $productStore = ProductStore::where('product_id', $item->product_id)
            ->where('store_id', $transaction->store_id)
            ->first();

        if (! $productStore) {
            return;
        }

        if ($diff > 0) {
            // More sold → decrement
            $productStore->decrement('quantity', $diff);
        } else {
            // Less sold → increment (restore)
            $productStore->increment('quantity', abs($diff));
        }

        $productStore->refresh();

        InventoryLog::create([
            'product_id' => $item->product_id,
            'store_id' => $transaction->store_id,
            'transaction_id' => $transaction->id,
            'activity_code' => InventoryActivityCodes::TRANSACTION_ADJUSTMENT,
            'quantity_in' => $diff < 0 ? abs($diff) : 0,
            'quantity_out' => $diff > 0 ? $diff : 0,
            'current_quantity' => $productStore->quantity,
            'notes' => "Adjustment: qty {$oldQty} → {$newQty} ({$transaction->transaction_number})",
            'created_by' => $userId,
        ]);
    }

    /**
     * Create a version snapshot.
     */
    protected function createVersion(
        Transaction $transaction,
        TransactionChangeType $changeType,
        int $userId,
        string $summary
    ): void {
        $transaction->loadMissing(['items', 'payments']);
        $transaction->increment('version_count');

        $transaction->versions()->create([
            'version_number' => $transaction->version_count,
            'change_type' => $changeType,
            'changed_by' => $userId,
            'change_summary' => $summary,
            'snapshot_items' => $transaction->items->toArray(),
            'snapshot_payments' => $transaction->payments->toArray(),
            'snapshot_totals' => [
                'subtotal' => (string) $transaction->subtotal,
                'offer_discount' => (string) $transaction->offer_discount,
                'bundle_discount' => (string) $transaction->bundle_discount,
                'minimum_spend_discount' => (string) $transaction->minimum_spend_discount,
                'customer_discount' => (string) $transaction->customer_discount,
                'manual_discount' => (string) $transaction->manual_discount,
                'tax_amount' => (string) $transaction->tax_amount,
                'total' => (string) $transaction->total,
                'amount_paid' => (string) $transaction->amount_paid,
                'balance_due' => (string) $transaction->balance_due,
                'change_amount' => (string) $transaction->change_amount,
            ],
        ]);
    }

    /**
     * Send notification emails to registered recipients for a transaction event.
     */
    protected function sendTransactionNotification(Transaction $transaction, NotificationEventType $eventType, Mailable $mailable): void
    {
        $recipients = NotificationRecipient::forEventType($eventType)
            ->where('store_id', $transaction->store_id)
            ->active()
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $transaction->loadMissing(['items.product', 'payments', 'employee', 'store', 'customer', 'currency']);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(clone $mailable);
        }
    }

    /**
     * Send amended transaction notification (for use by controllers on post-completion modifications).
     */
    public function sendAmendedNotification(Transaction $transaction, string $changeSummary): void
    {
        $transaction->loadMissing(['items.product', 'payments', 'employee', 'store', 'customer', 'currency']);
        $this->sendTransactionNotification(
            $transaction,
            NotificationEventType::AmendedTransaction,
            new TransactionAmendedMail($transaction, $changeSummary)
        );
    }
}
