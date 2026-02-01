<?php

namespace App\DataMigration\Models\Legacy;

class UserStore extends LegacyModel
{
    protected $table = 'users_stores';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
