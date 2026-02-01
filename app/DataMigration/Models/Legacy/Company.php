<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'companies';
}
