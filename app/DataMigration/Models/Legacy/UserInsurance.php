<?php

namespace App\DataMigration\Models\Legacy;

class UserInsurance extends LegacyModel
{
    protected $table = 'users_insurances';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
