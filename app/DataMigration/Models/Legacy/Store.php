<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'stores';

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
