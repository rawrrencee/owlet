<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'items';

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function itemStores()
    {
        return $this->hasMany(ItemStore::class, 'item_id');
    }
}
