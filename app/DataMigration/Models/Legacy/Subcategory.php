<?php

namespace App\DataMigration\Models\Legacy;

class Subcategory extends LegacyModel
{
    protected $table = 'categories_subcategories';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
