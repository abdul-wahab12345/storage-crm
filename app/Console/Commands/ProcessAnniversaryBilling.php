<?php

namespace App\Console\Commands;

use App\Events\InvoiceGenerated;
use App\Models\Invoice;
use App\Models\Lease;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAnniversaryBilling extends Command
{
    protected $signature = 'billing:process-anniversary {--dry-run : Show what would be billed without creating invoices}';

    protected $description = 'Generate invoices for active leases whose billing anniversary matches today';

    public function handle(): int
    {
        $today = now();
        $billingDay = $today->day;
        $isDryRun = $this->option('dry-run');

        $this->info("Processing anniversary billing for day {$billingDay} of the month...");

        $leases = Lease::active()
            ->where('billing_day', $billingDay)
            ->with(['tenant', 'unit.facility'])
            ->get();

        if ($leases->isEmpty()) {
            $this->info('No leases to bill today.');
            return self::SUCCESS;
        }

        $this->info("Found {$leases->count()} lease(s) to bill.");

        $created = 0;
        $skipped = 0;

        foreach ($leases as $lease) {
            $monthsSinceMoveIn = $today->copy()->startOfDay()->diffInMonths($lease->move_in_date->copy()->startOfDay());
            $interval = $lease->billing_interval_months ?: 1;

            if ($monthsSinceMoveIn % $interval !== 0) {
                // Not the correct month for billing based on interval
                continue;
            }

            $periodStart = $today->copy()->startOfDay();
            $periodEnd = $today->copy()->addMonths($interval)->subDay()->endOfDay();

            $existingInvoice = Invoice::where('lease_id', $lease->id)
                ->where('period_start', $periodStart->toDateString())
                ->exists();

            if ($existingInvoice) {
                $this->warn("  Skipped: Lease #{$lease->id} ({$lease->tenant->full_name}) — invoice already exists for this period.");
                $skipped++;
                continue;
            }

            if ($isDryRun) {
                $this->line("  [DRY RUN] Would bill: {$lease->tenant->full_name} — Unit {$lease->unit->unit_number} — \${$lease->monthly_rate} (Interval: {$interval} months)");
                $created++;
                continue;
            }

            try {
                DB::transaction(function () use ($lease, $periodStart, $periodEnd, &$created) {
                    $invoice = Invoice::create([
                        'lease_id' => $lease->id,
                        'tenant_id' => $lease->tenant_id,
                        'amount' => $lease->monthly_rate, // User confirmed no multiplication is needed
                        'late_fee' => 0,
                        'total' => $lease->monthly_rate,
                        'due_date' => $periodStart->toDateString(),
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                        'status' => 'pending',
                    ]);

                    event(new InvoiceGenerated($invoice));

                    $created++;
                    $this->info("  Created: INV {$invoice->invoice_number} for {$lease->tenant->full_name} — \${$invoice->total}");
                });
            } catch (\Throwable $e) {
                Log::error("Billing error for lease #{$lease->id}: {$e->getMessage()}");
                $this->error("  Error billing lease #{$lease->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Billing complete: {$created} created, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
