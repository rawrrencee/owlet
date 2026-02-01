<?php

namespace App\DataMigration\Models\Legacy;

class UserTeam extends LegacyModel
{
    protected $table = 'users_teams';

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
