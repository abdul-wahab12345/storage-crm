<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'billing:send-payment-reminders {--dry-run : Show what would be sent without sending}';

    protected $description = 'Send automated payment reminders for pending invoices';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $setting = \App\Models\Setting::get('reminder_days_before', '7,3,1');
        
        $daysArray = array_filter(array_map('trim', explode(',', $setting)));
        if (empty($daysArray)) {
            $this->info('No reminder days configured in settings.');
            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $this->info('Processing payment reminders...');

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($daysArray as $days) {
            if (!is_numeric($days) || $days < 0) continue;
            
            $days = (int) $days;
            $targetDate = $today->copy()->addDays($days);

            $invoices = \App\Models\Invoice::where('status', 'pending')
                ->whereDate('due_date', $targetDate)
                ->with('tenant')
                ->get();

            foreach ($invoices as $invoice) {
                if (!$invoice->tenant) continue;

                $tenant = $invoice->tenant;
                $channels = [];

                if ($tenant->email) {
                    $channels[] = 'email';
                }
                if ($tenant->whatsapp_number) {
                    $channels[] = 'whatsapp';
                }

                if (empty($channels)) {
                    $this->warn("  Skipped: Invoice {$invoice->invoice_number} — Tenant has no email or WhatsApp.");
                    $skippedCount++;
                    continue;
                }

                $shouldSend = false;
                $channelsToSend = [];

                foreach ($channels as $channel) {
                    $alreadySent = \App\Models\PaymentReminderLog::where('invoice_id', $invoice->id)
                        ->where('days_before', $days)
                        ->where('channel', $channel)
                        ->exists();

                    if (!$alreadySent) {
                        $shouldSend = true;
                        $channelsToSend[] = $channel;
                    }
                }

                if (!$shouldSend) {
                    $this->line("  Skipped: Invoice {$invoice->invoice_number} ({$days} days) — already sent.");
                    $skippedCount++;
                    continue;
                }

                if ($isDryRun) {
                    $this->line("  [DRY RUN] Would send {$days}-day reminder for Invoice {$invoice->invoice_number} via " . implode(', ', $channelsToSend));
                    $sentCount++;
                    continue;
                }

                try {
                    // We only notify via Tenant, the Notification itself handles WhatsApp and Email.
                    // But to respect the log per-channel, we send it once and log for the available channels.
                    $tenant->notify(new \App\Notifications\PaymentReminderNotification($invoice, $days));

                    foreach ($channelsToSend as $channel) {
                        \App\Models\PaymentReminderLog::create([
                            'invoice_id' => $invoice->id,
                            'days_before' => $days,
                            'channel' => $channel,
                        ]);
                    }

                    $sentCount++;
                    $this->info("  Sent {$days}-day reminder: Invoice {$invoice->invoice_number} via " . implode(', ', $channelsToSend));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Reminder error for invoice {$invoice->invoice_number}: {$e->getMessage()}");
                    $this->error("  Error sending reminder for invoice {$invoice->invoice_number}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("Reminders complete: {$sentCount} sent, {$skippedCount} skipped.");

        return self::SUCCESS;
    }
}
