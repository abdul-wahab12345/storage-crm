<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_full_name()
    {
        $tenant = Tenant::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        $this->assertEquals('John Doe', $tenant->full_name);
    }

    public function test_it_calculates_outstanding_balance()
    {
        $facility = \App\Models\Facility::create([
            'name' => 'Main',
            'address' => '123 Main',
        ]);
        
        $unit = \App\Models\Unit::create([
            'facility_id' => $facility->id,
            'unit_number' => 'A1',
            'monthly_price' => 100,
            'size' => '10x10',
        ]);

        $tenant = Tenant::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'move_in_date' => now(),
            'status' => 'active',
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'lease_id' => $lease->id,
            'status' => 'pending',
            'total' => 100.50,
            'amount' => 100.50,
            'due_date' => now(),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'lease_id' => $lease->id,
            'status' => 'overdue',
            'total' => 50.25,
            'amount' => 50.25,
            'due_date' => now()->subDay(),
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'lease_id' => $lease->id,
            'status' => 'paid',
            'total' => 200.00,
            'amount' => 200.00,
            'due_date' => now()->subDays(10),
            'period_start' => now()->subMonths(2)->startOfMonth(),
            'period_end' => now()->subMonths(2)->endOfMonth(),
        ]);

        $this->assertEquals(150.75, $tenant->outstanding_balance);
    }

    public function test_route_notification_for_mail()
    {
        $tenant = new Tenant(['email' => 'test@example.com']);
        $this->assertEquals('test@example.com', $tenant->routeNotificationForMail());
    }

    public function test_route_notification_for_whats_app()
    {
        $tenant = new Tenant(['whatsapp_number' => '+1234567890']);
        $this->assertEquals('+1234567890', $tenant->routeNotificationForWhatsApp());
    }
}
