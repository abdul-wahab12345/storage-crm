<?php

namespace App\Console\Commands;

use App\Models\MarketingCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DispatchScheduledCampaigns extends Command
{
    protected $signature   = 'marketing:dispatch-scheduled';
    protected $description = 'Send any marketing campaigns whose scheduled_at time has arrived';

    public function handle(): int
    {
        $due = MarketingCampaign::query()
            ->where('status', 'draft')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled campaigns due.');
            return self::SUCCESS;
        }

        foreach ($due as $campaign) {
            $this->info("Dispatching campaign #{$campaign->id}: {$campaign->name}");
            Artisan::call('marketing:send', ['campaign' => $campaign->id]);
            $this->line(Artisan::output());
        }

        return self::SUCCESS;
    }
}
