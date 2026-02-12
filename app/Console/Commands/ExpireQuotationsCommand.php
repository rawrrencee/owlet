<?php

namespace App\Console\Commands;

use App\Services\QuotationService;
use Illuminate\Console\Command;

class ExpireQuotationsCommand extends Command
{
    protected $signature = 'quotations:expire';

    protected $description = 'Expire quotations whose validity date has passed';

    public function handle(QuotationService $service): int
    {
        $count = $service->expireQuotations();

        if ($count > 0) {
            $this->info("Expired {$count} quotation(s).");
        } else {
            $this->info('No quotations to expire.');
        }

        return self::SUCCESS;
    }
}
