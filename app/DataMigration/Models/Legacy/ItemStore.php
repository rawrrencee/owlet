<?php

namespace App\DataMigration\Models\Legacy;

class ItemStore extends LegacyModel
{
    protected $table = 'items_stores';

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
