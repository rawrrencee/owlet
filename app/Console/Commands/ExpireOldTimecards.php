<?php

namespace App\Console\Commands;

use App\Services\TimecardService;
use Illuminate\Console\Command;

class ExpireOldTimecards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timecards:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark old in-progress timecards as expired';

    /**
     * Execute the console command.
     */
    public function handle(TimecardService $timecardService): int
    {
        $count = $timecardService->expireOldTimecards();

        $this->info("Expired {$count} timecard(s).");

        return Command::SUCCESS;
    }
}
