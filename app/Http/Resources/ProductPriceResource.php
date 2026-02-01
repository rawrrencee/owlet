<?php

namespace App\Http\Resources;

use App\Constants\PagePermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewCostPrice = $this->userCanViewCostPrice($request);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'name' => $this->currency->name,
                'symbol' => $this->currency->symbol,
                'decimal_places' => $this->currency->decimal_places,
            ]),
            'cost_price' => $this->when($canViewCostPrice, fn () => $this->cost_price),
            'unit_price' => $this->unit_price,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function userCanViewCostPrice(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $employee = $user->employee;
        if (! $employee) {
            return false;
        }

        $permissions = $employee->permissions()->pluck('permission')->toArray();

        return in_array(PagePermissions::PRODUCTS_VIEW_COST_PRICE, $permissions);
    }
}
