<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HierarchyVisibilitySetting extends Model
{
    protected $fillable = [
        'manager_id',
        'visible_sections',
    ];

    protected function casts(): array
    {
        return [
            'visible_sections' => 'array',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Check if a specific section is visible for this manager.
     */
    public function canView(string $section): bool
    {
        $sections = $this->visible_sections ?? [];

        return in_array($section, $sections, true);
    }

    /**
     * Get available visibility sections with labels.
     *
     * @return array<string, string>
     */
    public static function getAvailableSections(): array
    {
        return [
            'companies' => 'Company Assignments',
            'stores' => 'Store Assignments',
            // Future sections can be added here:
            // 'transactions' => 'Transactions',
            // 'delivery_orders' => 'Delivery Orders',
            // 'salary' => 'Salary Information',
        ];
    }
}
