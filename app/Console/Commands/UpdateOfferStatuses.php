<?php

namespace App\Console\Commands;

use App\Services\OfferService;
use Illuminate\Console\Command;

class UpdateOfferStatuses extends Command
{
    protected $signature = 'offers:update-statuses';

    protected $description = 'Update offer statuses: activate scheduled offers and expire ended offers';

    public function handle(OfferService $offerService): int
    {
        $count = $offerService->updateStatuses();

        $this->info("Updated {$count} offer status(es).");

        return Command::SUCCESS;
    }
}
