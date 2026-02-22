<?php

namespace App\Services;

use App\Enums\NotificationEventType;
use App\Enums\QuotationStatus;
use App\Mail\QuotationNotificationMail;
use App\Models\NotificationRecipient;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class QuotationService
{
    public function __construct(
        protected OfferService $offerService
    ) {}

    public function create(array $data, User $user): Quotation
    {
        $quotation = DB::transaction(function () use ($data, $user) {
            $quotation = Quotation::create([
                'quotation_number' => Quotation::generateQuotationNumber(),
                'company_id' => $data['company_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'status' => QuotationStatus::DRAFT,
                'show_company_logo' => $data['show_company_logo'] ?? true,
                'show_company_address' => $data['show_company_address'] ?? true,
                'show_company_uen' => $data['show_company_uen'] ?? true,
                'tax_mode' => $data['tax_mode'] ?? 'none',
                'tax_store_id' => $data['tax_store_id'] ?? null,
                'tax_percentage' => $data['tax_percentage'] ?? null,
                'tax_inclusive' => $data['tax_inclusive'] ?? false,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'external_notes' => $data['external_notes'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'validity_date' => $data['validity_date'] ?? null,
                'customer_discount_percentage' => $data['customer_discount_percentage'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncItems($quotation, $data['items'] ?? []);
            $this->syncPaymentModes($quotation, $data['payment_mode_ids'] ?? []);

            return $quotation->load('items.product', 'items.currency', 'paymentModes');
        });

        $this->sendNotification($quotation, 'Created');

        return $quotation;
    }

    public function update(Quotation $quotation, array $data, User $user): Quotation
    {
        abort_unless(
            $quotation->status === QuotationStatus::DRAFT,
            422,
            'Only draft quotations can be edited.'
        );

        return DB::transaction(function () use ($quotation, $data) {
            $quotation->update([
                'company_id' => $data['company_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'show_company_logo' => $data['show_company_logo'] ?? true,
                'show_company_address' => $data['show_company_address'] ?? true,
                'show_company_uen' => $data['show_company_uen'] ?? true,
                'tax_mode' => $data['tax_mode'] ?? 'none',
                'tax_store_id' => $data['tax_store_id'] ?? null,
                'tax_percentage' => $data['tax_percentage'] ?? null,
                'tax_inclusive' => $data['tax_inclusive'] ?? false,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'external_notes' => $data['external_notes'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'validity_date' => $data['validity_date'] ?? null,
                'customer_discount_percentage' => $data['customer_discount_percentage'] ?? null,
            ]);

            $this->syncItems($quotation, $data['items'] ?? []);
            $this->syncPaymentModes($quotation, $data['payment_mode_ids'] ?? []);

            return $quotation->fresh()->load('items.product', 'items.currency', 'paymentModes');
        });
    }

    public function delete(Quotation $quotation): void
    {
        abort_unless(
            $quotation->status === QuotationStatus::DRAFT,
            422,
            'Only draft quotations can be deleted.'
        );

        $quotation->delete();
    }

    public function markAsSent(Quotation $quotation, User $user): Quotation
    {
        abort_unless(
            $quotation->status === QuotationStatus::DRAFT,
            422,
            'Only draft quotations can be sent.'
        );

        $quotation->update([
            'status' => QuotationStatus::SENT,
            'sent_at' => now(),
        ]);

        $this->sendNotification($quotation, 'Sent');

        return $quotation;
    }

    public function revertToDraft(Quotation $quotation): Quotation
    {
        abort_unless(
            $quotation->status === QuotationStatus::SENT,
            422,
            'Only sent quotations can be reverted to draft.'
        );

        $quotation->update([
            'status' => QuotationStatus::DRAFT,
            'sent_at' => null,
        ]);

        $this->sendNotification($quotation, 'Reverted to Draft');

        return $quotation;
    }

    public function markAsAccepted(Quotation $quotation): Quotation
    {
        abort_unless(
            in_array($quotation->status, [QuotationStatus::SENT, QuotationStatus::VIEWED, QuotationStatus::SIGNED]),
            422,
            'Only sent, viewed, or signed quotations can be marked as accepted.'
        );

        $quotation->update([
            'status' => QuotationStatus::ACCEPTED,
        ]);

        $this->sendNotification($quotation, 'Accepted');

        return $quotation;
    }

    public function markAsPaid(Quotation $quotation): Quotation
    {
        abort_unless(
            $quotation->status === QuotationStatus::ACCEPTED,
            422,
            'Only accepted quotations can be marked as paid.'
        );

        $quotation->update([
            'status' => QuotationStatus::PAID,
        ]);

        $this->sendNotification($quotation, 'Paid');

        return $quotation;
    }

    public function generateShareToken(Quotation $quotation, ?string $password = null): string
    {
        if ($quotation->share_token) {
            if ($password !== null) {
                $quotation->update([
                    'share_password_hash' => Hash::make($password),
                ]);
            }

            return $quotation->share_token;
        }

        $token = \Illuminate\Support\Str::random(64);
        $data = ['share_token' => $token];

        if ($password !== null) {
            $data['share_password_hash'] = Hash::make($password);
        }

        $quotation->update($data);

        return $token;
    }

    public function updateSharePassword(Quotation $quotation, ?string $password): void
    {
        $quotation->update([
            'share_password_hash' => $password ? Hash::make($password) : null,
        ]);
    }

    public function duplicate(Quotation $quotation, User $user): Quotation
    {
        return DB::transaction(function () use ($quotation, $user) {
            $newQuotation = Quotation::create([
                'quotation_number' => Quotation::generateQuotationNumber(),
                'company_id' => $quotation->company_id,
                'customer_id' => $quotation->customer_id,
                'status' => QuotationStatus::DRAFT,
                'show_company_logo' => $quotation->show_company_logo,
                'show_company_address' => $quotation->show_company_address,
                'show_company_uen' => $quotation->show_company_uen,
                'tax_mode' => $quotation->tax_mode,
                'tax_store_id' => $quotation->tax_store_id,
                'tax_percentage' => $quotation->tax_percentage,
                'tax_inclusive' => $quotation->tax_inclusive,
                'terms_and_conditions' => $quotation->terms_and_conditions,
                'internal_notes' => $quotation->internal_notes,
                'external_notes' => $quotation->external_notes,
                'payment_terms' => $quotation->payment_terms,
                'validity_date' => null,
                'customer_discount_percentage' => $quotation->customer_discount_percentage,
                'created_by' => $user->id,
            ]);

            // Clone items
            foreach ($quotation->items as $item) {
                $newQuotation->items()->create([
                    'product_id' => $item->product_id,
                    'currency_id' => $item->currency_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'sort_order' => $item->sort_order,
                    'offer_id' => $item->offer_id,
                    'offer_name' => $item->offer_name,
                    'offer_discount_type' => $item->offer_discount_type,
                    'offer_discount_amount' => $item->offer_discount_amount,
                    'offer_is_combinable' => $item->offer_is_combinable,
                    'customer_discount_percentage' => $item->customer_discount_percentage,
                    'customer_discount_amount' => $item->customer_discount_amount,
                    'line_subtotal' => $item->line_subtotal,
                    'line_discount' => $item->line_discount,
                    'line_total' => $item->line_total,
                ]);
            }

            // Clone payment modes
            $newQuotation->paymentModes()->sync($quotation->paymentModes->pluck('id'));

            return $newQuotation;
        });
    }

    /**
     * Find the best product-level offer across all stores in a company.
     */
    public function resolveProductOffer(Product $product, int $companyId, int $currencyId, string $unitPrice, int $quantity = 1): ?array
    {
        $storeIds = Store::where('company_id', $companyId)->pluck('id');

        // If company has no stores, still check global offers (apply_to_all_stores)
        if ($storeIds->isEmpty()) {
            $storeIds = collect([null]);
        }

        $bestOffer = null;
        $bestDiscount = '0';

        foreach ($storeIds as $storeId) {
            // Check product-level offers
            $offer = $this->offerService->findBestProductOffer($product, $storeId, $currencyId, $unitPrice);
            if ($offer && bccomp($offer['discount_amount'], $bestDiscount, 4) > 0) {
                $bestDiscount = $offer['discount_amount'];
                $bestOffer = $offer;
            }

            // Check single-product bundle offers that this product + quantity satisfies
            $cartItems = [
                $product->id => [
                    'quantity' => $quantity,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                ],
            ];
            $bundleOffers = $this->offerService->findApplicableBundleOffers($cartItems, $storeId, $currencyId);
            foreach ($bundleOffers as $bundleOffer) {
                // Calculate per-unit discount from bundle offer
                $bundleAmount = $bundleOffer['amount'];
                $perUnitDiscount = null;

                if ($bundleOffer['discount_type'] === 'percentage' && $bundleOffer['discount_percentage']) {
                    $perUnitDiscount = bcmul($unitPrice, bcdiv((string) $bundleOffer['discount_percentage'], '100', 6), 4);
                    if ($bundleAmount?->max_discount_amount && bccomp($perUnitDiscount, (string) $bundleAmount->max_discount_amount, 4) > 0) {
                        $perUnitDiscount = (string) $bundleAmount->max_discount_amount;
                    }
                } elseif ($bundleOffer['discount_type'] === 'fixed' && $bundleAmount?->discount_amount) {
                    $perUnitDiscount = (string) $bundleAmount->discount_amount;
                    if (bccomp($perUnitDiscount, $unitPrice, 4) > 0) {
                        $perUnitDiscount = $unitPrice;
                    }
                }

                if ($perUnitDiscount && bccomp($perUnitDiscount, $bestDiscount, 4) > 0) {
                    $bestDiscount = $perUnitDiscount;
                    $bestOffer = [
                        'offer_id' => $bundleOffer['offer_id'],
                        'offer_name' => $bundleOffer['offer_name'],
                        'discount_type' => $bundleOffer['discount_type'],
                        'discount_percentage' => $bundleOffer['discount_percentage'] ? (float) $bundleOffer['discount_percentage'] : null,
                        'discount_amount' => $perUnitDiscount,
                        'is_combinable' => $bundleOffer['is_combinable'],
                    ];
                }
            }
        }

        return $bestOffer;
    }

    /**
     * Compute multi-currency totals for a quotation.
     */
    public function computeTotals(Quotation $quotation): array
    {
        $quotation->loadMissing('items.currency');

        $currencyGroups = [];
        foreach ($quotation->items as $item) {
            $currencyId = $item->currency_id;
            if (! isset($currencyGroups[$currencyId])) {
                $currencyGroups[$currencyId] = [
                    'currency_id' => $currencyId,
                    'currency_code' => $item->currency->code ?? '',
                    'currency_symbol' => $item->currency->symbol ?? '',
                    'subtotal' => '0',
                    'discount' => '0',
                    'tax' => '0',
                    'total' => '0',
                ];
            }

            $currencyGroups[$currencyId]['subtotal'] = bcadd(
                $currencyGroups[$currencyId]['subtotal'],
                (string) $item->line_subtotal,
                4
            );
            $currencyGroups[$currencyId]['discount'] = bcadd(
                $currencyGroups[$currencyId]['discount'],
                (string) $item->line_discount,
                4
            );
        }

        // Apply tax
        foreach ($currencyGroups as &$group) {
            $afterDiscount = bcsub($group['subtotal'], $group['discount'], 4);

            if ($quotation->tax_mode !== 'none' && $quotation->tax_percentage) {
                $taxRate = bcdiv((string) $quotation->tax_percentage, '100', 6);
                if ($quotation->tax_inclusive) {
                    // Tax is already included in the price
                    $group['tax'] = bcsub(
                        $afterDiscount,
                        bcdiv($afterDiscount, bcadd('1', $taxRate, 6), 4),
                        4
                    );
                    $group['total'] = $afterDiscount;
                } else {
                    $group['tax'] = bcmul($afterDiscount, $taxRate, 4);
                    $group['total'] = bcadd($afterDiscount, $group['tax'], 4);
                }
            } else {
                $group['total'] = $afterDiscount;
            }
        }

        return array_values($currencyGroups);
    }

    /**
     * Expire quotations whose validity date has passed.
     */
    public function expireQuotations(): int
    {
        return Quotation::whereIn('status', [QuotationStatus::DRAFT, QuotationStatus::SENT])
            ->whereNotNull('validity_date')
            ->where('validity_date', '<', now()->startOfDay())
            ->update([
                'status' => QuotationStatus::EXPIRED,
                'expired_at' => now(),
            ]);
    }

    protected function syncItems(Quotation $quotation, array $items): void
    {
        $quotation->items()->delete();

        foreach ($items as $index => $item) {
            $unitPrice = (string) $item['unit_price'];
            $quantity = (int) $item['quantity'];
            $lineSubtotal = bcmul($unitPrice, (string) $quantity, 4);

            // Offer discount
            $offerDiscount = '0';
            $offerId = $item['offer_id'] ?? null;
            $offerName = $item['offer_name'] ?? null;
            $offerDiscountType = $item['offer_discount_type'] ?? null;
            $offerDiscountAmount = $item['offer_discount_amount'] ?? null;
            $offerIsCombinable = $item['offer_is_combinable'] ?? null;

            if ($offerDiscountAmount) {
                $offerDiscount = bcmul((string) $offerDiscountAmount, (string) $quantity, 4);
            }

            // Customer discount
            $customerDiscountPercentage = $item['customer_discount_percentage'] ?? null;
            $customerDiscountAmount = '0';

            if ($customerDiscountPercentage) {
                $customerDiscountAmount = bcmul(
                    $lineSubtotal,
                    bcdiv((string) $customerDiscountPercentage, '100', 6),
                    4
                );
            }

            // Determine effective discount based on combinability
            $lineDiscount = '0';
            if ($offerDiscount && $customerDiscountAmount) {
                if ($offerIsCombinable) {
                    $lineDiscount = bcadd($offerDiscount, $customerDiscountAmount, 4);
                } else {
                    $lineDiscount = bccomp($offerDiscount, $customerDiscountAmount, 4) >= 0
                        ? $offerDiscount
                        : $customerDiscountAmount;
                }
            } elseif ($offerDiscount) {
                $lineDiscount = $offerDiscount;
            } elseif ($customerDiscountAmount) {
                $lineDiscount = $customerDiscountAmount;
            }

            // Ensure discount doesn't exceed subtotal
            if (bccomp($lineDiscount, $lineSubtotal, 4) > 0) {
                $lineDiscount = $lineSubtotal;
            }

            $lineTotal = bcsub($lineSubtotal, $lineDiscount, 4);

            $quotation->items()->create([
                'product_id' => $item['product_id'],
                'currency_id' => $item['currency_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'sort_order' => $item['sort_order'] ?? $index,
                'offer_id' => $offerId,
                'offer_name' => $offerName,
                'offer_discount_type' => $offerDiscountType,
                'offer_discount_amount' => $offerDiscountAmount,
                'offer_is_combinable' => $offerIsCombinable,
                'customer_discount_percentage' => $customerDiscountPercentage,
                'customer_discount_amount' => $customerDiscountAmount !== '0' ? $customerDiscountAmount : null,
                'line_subtotal' => $lineSubtotal,
                'line_discount' => $lineDiscount,
                'line_total' => $lineTotal,
            ]);
        }
    }

    protected function syncPaymentModes(Quotation $quotation, array $paymentModeIds): void
    {
        $quotation->paymentModes()->sync($paymentModeIds);
    }

    protected function sendNotification(Quotation $quotation, string $action): void
    {
        // Quotations are company-wide, no direct store - notify all recipients for this event type
        $recipients = NotificationRecipient::forEventType(NotificationEventType::Quotation)
            ->active()
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $quotation->loadMissing(['items.product', 'customer', 'company']);

        $mailable = new QuotationNotificationMail($quotation, $action);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(clone $mailable);
        }
    }
}
