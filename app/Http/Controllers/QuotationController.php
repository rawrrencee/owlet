<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Resources\QuotationResource;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Store;
use App\Services\PermissionService;
use App\Services\QuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class QuotationController extends Controller
{
    public function __construct(
        private readonly QuotationService $service,
        private readonly PermissionService $permissionService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $query = Quotation::with(['company', 'customer', 'createdBy'])
            ->withCount('items');

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('status')) {
            $query->ofStatus($request->status);
        }
        if ($request->filled('company_id')) {
            $query->forCompany((int) $request->company_id);
        }
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        $quotations = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $companies = Company::select('id', 'company_name')
            ->where('active', true)
            ->orderBy('company_name')
            ->get();

        return Inertia::render('Quotations/Index', [
            'quotations' => QuotationResource::collection($quotations),
            'companies' => $companies,
            'filters' => $request->only(['search', 'status', 'company_id', 'date_from', 'date_to']),
            'statusOptions' => QuotationStatus::options(),
        ]);
    }

    public function create(): InertiaResponse
    {
        $companies = Company::select('id', 'company_name')
            ->where('active', true)
            ->orderBy('company_name')
            ->get();

        $currencies = Currency::where('active', true)
            ->select('id', 'code', 'name', 'symbol')
            ->orderBy('code')
            ->get();

        $paymentModes = PaymentMode::active()->ordered()
            ->select('id', 'name', 'code')
            ->get();

        $stores = Store::select('id', 'store_name', 'store_code', 'company_id', 'tax_percentage')
            ->orderBy('store_name')
            ->get();

        return Inertia::render('Quotations/Form', [
            'companies' => $companies,
            'currencies' => $currencies,
            'paymentModes' => $paymentModes,
            'stores' => $stores,
        ]);
    }

    public function store(StoreQuotationRequest $request): RedirectResponse
    {
        $quotation = $this->service->create($request->validated(), $request->user());

        return redirect("/quotations/{$quotation->id}")
            ->with('success', 'Quotation created.');
    }

    public function show(Quotation $quotation): InertiaResponse
    {
        $quotation->load([
            'company', 'customer', 'taxStore',
            'items.product', 'items.currency',
            'paymentModes', 'createdBy', 'updatedBy',
        ]);

        $user = auth()->user();
        $canCreate = $this->permissionService->canAccessPage($user, 'quotations.create');
        $canManage = $this->permissionService->canAccessPage($user, 'quotations.manage');
        $canAdmin = $this->permissionService->canAccessPage($user, 'quotations.admin');

        // Convert company logo to base64 data URI (same approach as Public page)
        $logoDataUri = null;
        if ($quotation->show_company_logo && $quotation->company?->logo) {
            $logoPath = storage_path('app/private/' . $quotation->company->logo);
            if (file_exists($logoPath)) {
                $mime = mime_content_type($logoPath);
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }
        }

        return Inertia::render('Quotations/View', [
            'quotation' => QuotationResource::make($quotation)->resolve(),
            'logoDataUri' => $logoDataUri,
            'canCreate' => $canCreate,
            'canManage' => $canManage,
            'canAdmin' => $canAdmin,
        ]);
    }

    public function edit(Quotation $quotation): InertiaResponse
    {
        abort_unless(
            $quotation->status === QuotationStatus::DRAFT,
            403,
            'Only draft quotations can be edited.'
        );

        $quotation->load([
            'company', 'customer', 'taxStore',
            'items.product', 'items.currency',
            'paymentModes',
        ]);

        $companies = Company::select('id', 'company_name')
            ->where('active', true)
            ->orderBy('company_name')
            ->get();

        $currencies = Currency::where('active', true)
            ->select('id', 'code', 'name', 'symbol')
            ->orderBy('code')
            ->get();

        $paymentModes = PaymentMode::active()->ordered()
            ->select('id', 'name', 'code')
            ->get();

        $stores = Store::select('id', 'store_name', 'store_code', 'company_id', 'tax_percentage')
            ->orderBy('store_name')
            ->get();

        return Inertia::render('Quotations/Form', [
            'quotation' => QuotationResource::make($quotation)->resolve(),
            'companies' => $companies,
            'currencies' => $currencies,
            'paymentModes' => $paymentModes,
            'stores' => $stores,
        ]);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): RedirectResponse
    {
        $this->service->update($quotation, $request->validated(), $request->user());

        return redirect("/quotations/{$quotation->id}")
            ->with('success', 'Quotation updated.');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $this->service->delete($quotation);

        return redirect('/quotations')
            ->with('success', 'Quotation deleted.');
    }

    public function markAsSent(Quotation $quotation): RedirectResponse
    {
        $this->service->markAsSent($quotation, auth()->user());

        return redirect("/quotations/{$quotation->id}")
            ->with('success', 'Quotation marked as sent.');
    }

    public function duplicate(Quotation $quotation): RedirectResponse
    {
        $quotation->load('items', 'paymentModes');
        $newQuotation = $this->service->duplicate($quotation, auth()->user());

        return redirect("/quotations/{$newQuotation->id}")
            ->with('success', 'Quotation duplicated.');
    }

    public function revertToDraft(Quotation $quotation): RedirectResponse
    {
        $this->service->revertToDraft($quotation);

        return redirect("/quotations/{$quotation->id}")
            ->with('success', 'Quotation reverted to draft.');
    }

    public function markAsAccepted(Quotation $quotation): RedirectResponse
    {
        $this->service->markAsAccepted($quotation);

        return redirect("/quotations/{$quotation->id}")
            ->with('success', 'Quotation marked as accepted.');
    }

    public function markAsPaid(Quotation $quotation): RedirectResponse
    {
        $this->service->markAsPaid($quotation);

        return redirect("/quotations/{$quotation->id}")
            ->with('success', 'Quotation marked as paid.');
    }

    public function generateShareLink(Request $request, Quotation $quotation): JsonResponse
    {
        $password = $request->input('password');
        $token = $this->service->generateShareToken($quotation, $password ?: null);

        return response()->json([
            'share_token' => $token,
            'share_url' => url("/q/{$token}"),
            'has_password' => (bool) $quotation->fresh()->share_password_hash,
        ]);
    }

    public function updateSharePassword(Request $request, Quotation $quotation): JsonResponse
    {
        $request->validate([
            'password' => ['nullable', 'string', 'min:4'],
        ]);

        $this->service->updateSharePassword($quotation, $request->input('password'));

        return response()->json([
            'has_password' => (bool) $request->input('password'),
        ]);
    }

    public function downloadPdf(Quotation $quotation): HttpResponse
    {
        $quotation->load([
            'company', 'customer', 'taxStore',
            'items.product', 'items.currency',
            'paymentModes',
        ]);

        $totals = $this->service->computeTotals($quotation);

        // Convert company logo to base64 for DOMPDF
        $logoBase64 = null;
        if ($quotation->show_company_logo && $quotation->company?->logo) {
            $logoPath = storage_path('app/private/' . $quotation->company->logo);
            if (file_exists($logoPath)) {
                $mime = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }
        }

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'totals' => $totals,
            'logoBase64' => $logoBase64,
        ]);

        $filename = "{$quotation->quotation_number}.pdf";

        return $pdf->download($filename);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $search = $request->query('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->search($search)
            ->with('prices')
            ->limit(20)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'product_number' => $product->product_number,
                'variant_name' => $product->variant_name,
                'barcode' => $product->barcode,
                'image_url' => $product->image_path ? route('products.image', $product->id) : null,
                'prices' => $product->prices->map(fn ($p) => [
                    'currency_id' => $p->currency_id,
                    'unit_price' => $p->unit_price,
                ]),
            ]);

        return response()->json($products);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->query('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get()
            ->map(fn ($customer) => [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'discount_percentage' => $customer->discount_percentage,
            ]);

        return response()->json($customers);
    }

    public function resolveOffer(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($request->product_id);
        $offer = $this->service->resolveProductOffer(
            $product,
            (int) $request->company_id,
            (int) $request->currency_id,
            (string) $request->unit_price,
            (int) ($request->quantity ?? 1)
        );

        return response()->json($offer);
    }
}
