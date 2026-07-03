<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_can_render_index_page()
    {
        $this->actingAs($this->user)
            ->get(TenantResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_can_render_create_page()
    {
        $this->actingAs($this->user)
            ->get(TenantResource::getUrl('create'))
            ->assertSuccessful();
    }

    public function test_can_render_edit_page()
    {
        $tenant = Tenant::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        $this->actingAs($this->user)
            ->get(TenantResource::getUrl('edit', ['record' => $tenant]))
            ->assertSuccessful();
    }
}
