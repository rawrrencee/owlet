<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentModeRequest;
use App\Http\Requests\UpdatePaymentModeRequest;
use App\Http\Resources\PaymentModeResource;
use App\Models\PaymentMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PaymentModeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = PaymentMode::with(['createdBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $paymentModes = $query->ordered()
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('PaymentModes/Index', [
            'paymentModes' => PaymentModeResource::collection($paymentModes),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('PaymentModes/Form');
    }

    public function store(StorePaymentModeRequest $request): RedirectResponse
    {
        PaymentMode::create($request->validated());

        return redirect('/payment-modes')
            ->with('success', 'Payment mode created.');
    }

    public function edit(PaymentMode $paymentMode): InertiaResponse
    {
        return Inertia::render('PaymentModes/Form', [
            'paymentMode' => PaymentModeResource::make($paymentMode)->resolve(),
        ]);
    }

    public function update(UpdatePaymentModeRequest $request, PaymentMode $paymentMode): RedirectResponse
    {
        $paymentMode->update($request->validated());

        return redirect('/payment-modes')
            ->with('success', 'Payment mode updated.');
    }

    public function destroy(PaymentMode $paymentMode): RedirectResponse
    {
        $paymentMode->delete();

        return redirect('/payment-modes')
            ->with('success', 'Payment mode deleted.');
    }
}
