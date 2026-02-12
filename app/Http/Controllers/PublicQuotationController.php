<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Http\Resources\QuotationItemResource;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PublicQuotationController extends Controller
{
    public function __construct(
        private readonly QuotationService $service
    ) {}

    public function show(Request $request, string $shareToken): InertiaResponse
    {
        $quotation = Quotation::where('share_token', $shareToken)->firstOrFail();

        // Check if password is required
        if ($quotation->share_password_hash && ! $request->session()->get("quotation_access_{$quotation->id}")) {
            return Inertia::render('Quotations/Public', [
                'quotation' => null,
                'logoDataUri' => null,
                'requiresPassword' => true,
                'shareToken' => $shareToken,
                'passwordError' => session('passwordError'),
            ]);
        }

        $quotation->load([
            'company', 'customer', 'taxStore',
            'items.product', 'items.currency',
            'paymentModes',
        ]);

        // Track viewed_at and transition SENT -> VIEWED
        if (! $quotation->viewed_at) {
            $quotation->update(['viewed_at' => now()]);
        }

        if ($quotation->status === QuotationStatus::SENT) {
            $quotation->update(['status' => QuotationStatus::VIEWED]);
        }

        $totals = $this->service->computeTotals($quotation);

        // Convert company logo to base64 data URI for public access
        $logoDataUri = null;
        if ($quotation->show_company_logo && $quotation->company?->logo) {
            $logoPath = storage_path('app/private/' . $quotation->company->logo);
            if (file_exists($logoPath)) {
                $mime = mime_content_type($logoPath);
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }
        }

        return Inertia::render('Quotations/Public', [
            'quotation' => [
                'id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'company' => $quotation->company ? [
                    'company_name' => $quotation->company->company_name,
                    'registration_number' => $quotation->company->registration_number,
                    'address_1' => $quotation->company->address_1,
                    'address_2' => $quotation->company->address_2,
                    'city' => $quotation->company->city,
                    'postal_code' => $quotation->company->postal_code,
                    'email' => $quotation->company->email,
                    'phone_number' => $quotation->company->phone_number,
                ] : null,
                'customer' => $quotation->customer ? [
                    'full_name' => $quotation->customer->full_name,
                    'email' => $quotation->customer->email,
                    'phone' => $quotation->customer->phone,
                ] : null,
                'status' => $quotation->status->value,
                'status_label' => $quotation->status->label(),
                'show_company_logo' => $quotation->show_company_logo,
                'show_company_address' => $quotation->show_company_address,
                'show_company_uen' => $quotation->show_company_uen,
                'tax_mode' => $quotation->tax_mode,
                'tax_percentage' => $quotation->tax_percentage,
                'tax_inclusive' => $quotation->tax_inclusive,
                'terms_and_conditions' => $quotation->terms_and_conditions,
                'external_notes' => $quotation->external_notes,
                'payment_terms' => $quotation->payment_terms,
                'validity_date' => $quotation->validity_date?->toDateString(),
                'customer_discount_percentage' => $quotation->customer_discount_percentage,
                'items' => QuotationItemResource::collection($quotation->items)->resolve(),
                'payment_modes' => $quotation->paymentModes->map(fn ($pm) => [
                    'id' => $pm->id,
                    'name' => $pm->name,
                ]),
                'totals' => $totals,
                'created_at' => $quotation->created_at?->toIso8601String(),
            ],
            'logoDataUri' => $logoDataUri,
            'requiresPassword' => false,
            'shareToken' => $shareToken,
        ]);
    }

    public function verifyPassword(Request $request, string $shareToken)
    {
        $quotation = Quotation::where('share_token', $shareToken)->firstOrFail();

        if (! $quotation->share_password_hash) {
            return redirect("/q/{$shareToken}");
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), $quotation->share_password_hash)) {
            return redirect("/q/{$shareToken}")->with('passwordError', 'Incorrect password.');
        }

        $request->session()->put("quotation_access_{$quotation->id}", true);

        return redirect("/q/{$shareToken}");
    }
}
