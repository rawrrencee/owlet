<?php

namespace App\Models\Concerns;

use App\Models\Product;

/**
 * Trait for models that should update the parent Product's audit trail
 * when they are created, updated, or deleted.
 */
trait TouchesProductAuditTrail
{
    public static function bootTouchesProductAuditTrail(): void
    {
        static::created(function ($model) {
            $model->touchProductAuditTrail();
        });

        static::updated(function ($model) {
            $model->touchProductAuditTrail();
        });

        static::deleted(function ($model) {
            $model->touchProductAuditTrail();
        });
    }

    /**
     * Update the parent Product's audit trail.
     */
    protected function touchProductAuditTrail(): void
    {
        $product = $this->getProductForAuditTrail();

        if (! $product) {
            return;
        }

        $product->touchAuditTrail();
    }

    /**
     * Get the Product model for audit trail updates.
     * Override this method in models where the relationship is indirect.
     */
    protected function getProductForAuditTrail(): ?Product
    {
        if (method_exists($this, 'product')) {
            return $this->product;
        }

        return null;
    }
}
