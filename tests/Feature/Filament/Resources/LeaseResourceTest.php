<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\LeaseResource;
use App\Models\Facility;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
        
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

    public function test_can_render_index_page()
    {
        $this->actingAs($this->user)
            ->get(LeaseResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_can_render_create_page()
    {
        $this->actingAs($this->user)
            ->get(LeaseResource::getUrl('create'))
            ->assertSuccessful();
    }

    public function test_can_render_edit_page()
    {
        $lease = Lease::create([
            'unit_id' => $this->unit->id,
            'tenant_id' => $this->tenant->id,
            'move_in_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->user)
            ->get(LeaseResource::getUrl('edit', ['record' => $lease]))
            ->assertSuccessful();
    }
}
