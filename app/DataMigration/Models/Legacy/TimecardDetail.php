<?php

namespace App\DataMigration\Models\Legacy;

class TimecardDetail extends LegacyModel
{
    protected $table = 'timecards_details';

    public function timecard()
    {
        return $this->belongsTo(Timecard::class, 'timecard_id');
    }
}
