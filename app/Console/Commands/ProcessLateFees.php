<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessLateFees extends Command
{
    protected $signature = 'billing:process-late-fees {--dry-run : Show what would be charged without applying fees}';

    protected $description = 'Apply late fees to overdue invoices past their grace period';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $today = now();

        $this->info('Processing late fees...');

        $pendingInvoices = Invoice::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->with(['lease.unit.facility', 'tenant'])
            ->get();

        if ($pendingInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            return self::SUCCESS;
        }

        $this->info("Found {$pendingInvoices->count()} overdue invoice(s) to evaluate.");

        $updated = 0;
        $skipped = 0;

        foreach ($pendingInvoices as $invoice) {
            $facility = $invoice->lease?->unit?->facility;

            if (! $facility) {
                $this->warn("  Skipped: Invoice {$invoice->invoice_number} — no facility found.");
                $skipped++;
                continue;
            }

            $graceDays = $facility->late_fee_grace_days;
            $daysPastDue = $today->diffInDays($invoice->due_date);

            if ($daysPastDue <= $graceDays) {
                $this->line("  Grace period: Invoice {$invoice->invoice_number} — {$daysPastDue} day(s) past due ({$graceDays} day grace).");
                $skipped++;
                continue;
            }

            if ($invoice->late_fee > 0) {
                $this->line("  Already applied: Invoice {$invoice->invoice_number} already has a late fee.");
                $skipped++;
                continue;
            }

            if ($invoice->custom_late_fee !== null) {
                $lateFee = (float) $invoice->custom_late_fee;
            } elseif ($facility->late_fee_type === 'percentage') {
                $lateFee = round($invoice->amount * ($facility->late_fee_amount / 100), 2);
            } else {
                $lateFee = (float) $facility->late_fee_amount;
            }

            if ($lateFee <= 0) {
                $skipped++;
                continue;
            }

            if ($isDryRun) {
                $this->line("  [DRY RUN] Would charge: Invoice {$invoice->invoice_number} ({$invoice->tenant->full_name}) — \${$lateFee} late fee.");
                $updated++;
                continue;
            }

            try {
                $invoice->update([
                    'late_fee' => $lateFee,
                    'total' => $invoice->amount + $lateFee,
                    'status' => 'overdue',
                ]);

                if ($invoice->lease?->unit) {
                    $invoice->lease->unit->update(['status' => 'overdue']);
                }

                $updated++;
                $this->info("  Applied: Invoice {$invoice->invoice_number} — \${$lateFee} late fee. New total: \${$invoice->total}");
            } catch (\Throwable $e) {
                Log::error("Late fee error for invoice {$invoice->invoice_number}: {$e->getMessage()}");
                $this->error("  Error processing invoice {$invoice->invoice_number}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Late fees complete: {$updated} applied, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
