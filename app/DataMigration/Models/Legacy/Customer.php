<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'customers';
}
