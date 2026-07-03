<?php

namespace Tests\Unit\Models;

use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->facility = Facility::create([
            'name' => 'Main Storage',
            'address' => '123 Main St',
        ]);

        $this->unit = Unit::create([
            'facility_id' => $this->facility->id,
            'unit_number' => 'A101',
            'size' => '10x10',
            'monthly_price' => 150.00,
            'status' => 'occupied',
        ]);

        $this->tenant = Tenant::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
        ]);

        $this->lease = Lease::create([
            'unit_id' => $this->unit->id,
            'tenant_id' => $this->tenant->id,
            'move_in_date' => now()->subMonths(2),
            'status' => 'active',
        ]);
    }

    public function test_it_generates_invoice_number_and_calculates_total_on_creation()
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'lease_id' => $this->lease->id,
            'amount' => 150.00,
            'late_fee' => 25.00,
            'due_date' => now()->addDays(5),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        $this->assertNotNull($invoice->invoice_number);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertEquals(175.00, $invoice->total);
    }

    public function test_it_calculates_amount_paid_and_balance_due()
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'lease_id' => $this->lease->id,
            'amount' => 200.00,
            'due_date' => now()->addDays(5),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 50.00,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'method' => 'card',
            'paid_at' => now(),
        ]);

        // Refresh is not needed for calculated attributes based on relationships if loaded correctly,
        // but it's safe to reload the relationship
        $invoice->load('payments');

        $this->assertEquals(150.00, $invoice->amount_paid);
        $this->assertEquals(50.00, $invoice->balance_due);
    }

    public function test_it_determines_if_overdue()
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'lease_id' => $this->lease->id,
            'amount' => 100.00,
            'status' => 'pending',
            'due_date' => now()->subDays(1),
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
        ]);

        $this->assertTrue($invoice->is_overdue);

        $invoice->update(['due_date' => now()->addDays(1)]);
        $this->assertFalse($invoice->is_overdue);

        $invoice->update(['status' => 'paid', 'due_date' => now()->subDays(1)]);
        $this->assertFalse($invoice->is_overdue);
    }

    public function test_it_can_mark_as_paid()
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'lease_id' => $this->lease->id,
            'amount' => 100.00,
            'status' => 'pending',
            'due_date' => now()->addDays(5),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        $this->assertNull($invoice->paid_at);

        $invoice->markAsPaid();

        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }
}
