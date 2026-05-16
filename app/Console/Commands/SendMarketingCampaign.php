<?php

namespace App\Console\Commands;

use App\Models\MarketingCampaign;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendMarketingCampaign extends Command
{
    protected $signature = 'marketing:send {campaign : Campaign ID}
                            {--dry-run : Show recipients without sending}
                            {--delay=200 : Milliseconds to wait between each API call}';

    protected $description = 'Send a WhatsApp marketing campaign to its audience';

    public function handle(WhatsAppService $whatsApp): int
    {
        $campaign = MarketingCampaign::find($this->argument('campaign'));

        if (! $campaign) {
            $this->error("Campaign #{$this->argument('campaign')} not found.");
            return self::FAILURE;
        }

        if ($campaign->status === 'sending') {
            $this->error("Campaign is already sending. Aborting to prevent duplicates.");
            return self::FAILURE;
        }

        if ($campaign->status === 'completed') {
            $this->warn("Campaign already completed ({$campaign->sent_count} sent). Use a new campaign to resend.");
            return self::FAILURE;
        }

        $isDryRun    = $this->option('dry-run');
        $delayMs     = (int) $this->option('delay');
        $query       = $campaign->buildTenantQuery();
        $total       = $query->count();

        $this->info("Campaign: {$campaign->name}");
        $this->info("Template: {$campaign->template_name} [{$campaign->language_code}]");
        $this->info("Audience: {$campaign->audience_type} — {$total} recipients with WhatsApp numbers");
        $this->newLine();

        if ($total === 0) {
            $this->warn('No recipients found. Check audience settings and that tenants have WhatsApp numbers.');
            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->info('[DRY RUN] Recipients:');
            $query->chunk(100, function ($tenants) {
                foreach ($tenants as $tenant) {
                    $this->line("  • {$tenant->full_name} — {$tenant->whatsapp_number}");
                }
            });
            $this->newLine();
            $this->info("[DRY RUN] Would send to {$total} recipients. No messages sent.");
            return self::SUCCESS;
        }

        // Mark as sending
        $campaign->update([
            'status'      => 'sending',
            'total_count' => $total,
            'sent_count'  => 0,
            'failed_count'=> 0,
            'started_at'  => now(),
        ]);

        $sent   = 0;
        $failed = 0;
        $bar    = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(100, function ($tenants) use (
            $campaign, $whatsApp, $delayMs, &$sent, &$failed, $bar
        ) {
            foreach ($tenants as $tenant) {
                $bodyParams = [];

                foreach (($campaign->body_variables ?? []) as $variable) {
                    $value = $campaign->resolveVariable($tenant, $variable);
                    // WhatsApp limits body text parameters to 30 characters
                    $value = mb_substr(trim($value) ?: '-', 0, 30);
                    $bodyParams[] = ['type' => 'text', 'text' => $value];
                }

                $success = $whatsApp->sendTemplateMessage(
                    to:                $tenant->whatsapp_number,
                    templateName:      $campaign->template_name,
                    languageCode:      $campaign->language_code,
                    bodyParams:        $bodyParams,
                    headerDocUrl:      $campaign->header_url ?: null,
                    headerDocFilename: $campaign->header_url ? 'document.pdf' : null,
                );

                $success ? $sent++ : $failed++;
                $bar->advance();

                // Respect rate limits between calls
                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }

            // Persist progress after each chunk
            $campaign->update([
                'sent_count'   => $sent,
                'failed_count' => $failed,
            ]);
        });

        $bar->finish();
        $this->newLine(2);

        $finalStatus = $failed === $total ? 'failed' : 'completed';
        $campaign->update([
            'status'       => $finalStatus,
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'completed_at' => now(),
        ]);

        $this->info("Done. Sent: {$sent}  Failed: {$failed}  Total: {$total}");

        if ($failed > 0) {
            $this->warn("Check laravel.log for individual failure details.");
        }

        return self::SUCCESS;
    }
}
