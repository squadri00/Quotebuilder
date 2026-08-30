<?php

namespace App\Console\Commands;

use App\Services\Affiliate\CommissionService;
use Illuminate\Console\Command;

class AffiliateReconcile extends Command
{
    protected $signature = 'affiliate:reconcile';

    protected $description = 'Expire lapsed prospect claims and back-fill any missed commission accruals';

    public function handle(CommissionService $service): int
    {
        $result = $service->reconcileAll();

        $this->info("Claims expired:      {$result['claims_expired']}");
        $this->info("Commissions accrued: {$result['commissions_accrued']}");

        return self::SUCCESS;
    }
}
