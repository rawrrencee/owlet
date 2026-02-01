<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'suppliers';
}
