<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'brands';
}
