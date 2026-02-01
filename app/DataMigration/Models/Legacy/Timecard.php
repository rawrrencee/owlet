<?php

namespace App\DataMigration\Models\Legacy;

class Timecard extends LegacyModel
{
    protected $table = 'timecards';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function details()
    {
        return $this->hasMany(TimecardDetail::class, 'timecard_id');
    }
}
