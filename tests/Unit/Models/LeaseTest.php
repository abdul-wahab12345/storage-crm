<?php

namespace Tests\Unit\Models;

use App\Models\Facility;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class LeaseTest extends TestCase
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
            'status' => 'available',
        ]);

        $this->tenant = Tenant::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
        ]);
    }

    public function test_it_sets_default_billing_day_and_rate_on_creation()
    {
        $moveInDate = Carbon::create(2023, 10, 15);
        
        $lease = Lease::create([
            'unit_id' => $this->unit->id,
            'tenant_id' => $this->tenant->id,
            'move_in_date' => $moveInDate,
            'status' => 'active',
        ]);

        $this->assertEquals(15, $lease->billing_day);
        $this->assertEquals(150.00, $lease->monthly_rate);
    }

    public function test_it_updates_unit_status_to_occupied_when_active()
    {
        $lease = Lease::create([
            'unit_id' => $this->unit->id,
            'tenant_id' => $this->tenant->id,
            'move_in_date' => now(),
            'status' => 'active',
        ]);

        $this->unit->refresh();
        $this->assertEquals('occupied', $this->unit->status);
    }

    public function test_it_updates_unit_status_to_available_when_terminated()
    {
        $lease = Lease::create([
            'unit_id' => $this->unit->id,
            'tenant_id' => $this->tenant->id,
            'move_in_date' => now(),
            'status' => 'active',
        ]);

        $this->unit->refresh();
        $this->assertEquals('occupied', $this->unit->status);

        $lease->update(['status' => 'terminated']);

        $this->unit->refresh();
        $this->assertEquals('available', $this->unit->status);
    }

    public function test_active_scope()
    {
        Lease::create([
            'unit_id' => $this->unit->id,
            'tenant_id' => $this->tenant->id,
            'move_in_date' => now(),
            'status' => 'active',
        ]);

        Lease::create([
            'unit_id' => $this->unit->id,
            'tenant_id' => $this->tenant->id,
            'move_in_date' => now(),
            'status' => 'terminated',
        ]);

        $this->assertEquals(1, Lease::active()->count());
    }
}
