<?php

namespace App\Console\Commands;

use App\Services\StocktakeService;
use Illuminate\Console\Command;

class ExpireOldStocktakes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stocktakes:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark old in-progress stocktakes as expired';

    /**
     * Execute the console command.
     */
    public function handle(StocktakeService $stocktakeService): int
    {
        $count = $stocktakeService->expireOldStocktakes();

        $this->info("Expired {$count} stocktake(s).");

        return Command::SUCCESS;
    }
}
