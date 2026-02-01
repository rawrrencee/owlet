<?php

namespace App\DataMigration\Models\Legacy;

class UserProfile extends LegacyModel
{
    protected $table = 'users_profiles';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
