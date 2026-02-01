<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Http\Resources\CurrencyResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CurrencyController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $perPage = min(max($request->integer('per_page', 15), 10), 100);

        $query = Currency::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        $currencies = $query
            ->paginate($perPage)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return CurrencyResource::collection($currencies)->response();
        }

        $transformedCurrencies = [
            'data' => CurrencyResource::collection($currencies->items())->resolve(),
            'current_page' => $currencies->currentPage(),
            'last_page' => $currencies->lastPage(),
            'per_page' => $currencies->perPage(),
            'total' => $currencies->total(),
        ];

        return Inertia::render('Currencies/Index', [
            'currencies' => $transformedCurrencies,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Currencies/Form', [
            'currency' => null,
        ]);
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse|JsonResponse
    {
        $currency = Currency::create($request->validated());

        return $this->respondWithCreated(
            $request,
            'currencies.index',
            [],
            'Currency created successfully.',
            new CurrencyResource($currency)
        );
    }

    public function show(Request $request, Currency $currency): InertiaResponse|JsonResponse
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new CurrencyResource($currency))->resolve(),
            ]);
        }

        return Inertia::render('Currencies/View', [
            'currency' => (new CurrencyResource($currency))->resolve(),
        ]);
    }

    public function edit(Currency $currency): InertiaResponse
    {
        return Inertia::render('Currencies/Form', [
            'currency' => (new CurrencyResource($currency))->resolve(),
        ]);
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse|JsonResponse
    {
        $currency->update($request->validated());

        return $this->respondWithSuccess(
            $request,
            'currencies.show',
            ['currency' => $currency->id],
            'Currency updated successfully.',
            (new CurrencyResource($currency->fresh()))->resolve()
        );
    }

    public function destroy(Request $request, Currency $currency): RedirectResponse|JsonResponse
    {
        // Check if currency is assigned to any stores
        if ($currency->storeCurrencies()->exists()) {
            return $this->respondWithError(
                $request,
                'Cannot delete currency. It is assigned to one or more stores.',
                [],
                422
            );
        }

        $currency->delete();

        return $this->respondWithDeleted(
            $request,
            'currencies.index',
            [],
            'Currency deleted successfully.'
        );
    }

    public function refreshRates(Request $request, CurrencyService $currencyService): RedirectResponse|JsonResponse
    {
        $result = $currencyService->refreshExchangeRates();

        $message = "Exchange rates refreshed. Updated: {$result['updated']}, Failed: {$result['failed']} (Base: {$result['base_currency']})";

        return $this->respondWithSuccess(
            $request,
            'currencies.index',
            [],
            $message,
            $result
        );
    }
}
