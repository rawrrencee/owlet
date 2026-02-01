<?php

namespace App\DataMigration\Models\Legacy;

class UserCompany extends LegacyModel
{
    protected $table = 'users_companies';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
}
