<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'categories';

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id');
    }
}
