<?php

namespace App\DataMigration\Models\Legacy;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends LegacyModel
{
    use SoftDeletes;

    protected $table = 'users';

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function userCompanies()
    {
        return $this->hasMany(UserCompany::class, 'user_id');
    }

    public function userStores()
    {
        return $this->hasMany(UserStore::class, 'user_id');
    }

    public function userContracts()
    {
        return $this->hasMany(UserContract::class, 'user_id');
    }

    public function userInsurances()
    {
        return $this->hasMany(UserInsurance::class, 'user_id');
    }

    public function userTeams()
    {
        return $this->hasMany(UserTeam::class, 'leader_id');
    }
}
