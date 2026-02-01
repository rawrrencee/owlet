<?php

namespace App\DataMigration\Models\Legacy;

class UserContract extends LegacyModel
{
    protected $table = 'users_contracts';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
