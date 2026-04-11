<?php

namespace App\Filament\Widgets;

use App\Models\Facility;
use App\Models\Unit;
use Filament\Widgets\Widget;

class FacilityMapWidget extends Widget
{
    protected static string $view = 'filament.widgets.facility-map';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public ?int $selectedFacilityId = null;

    public ?array $selectedUnit = null;

    public function mount(): void
    {
        $this->selectedFacilityId = Facility::first()?->id;
    }

    public function getFacilitiesProperty()
    {
        return Facility::where('is_active', true)->pluck('name', 'id');
    }

    public function getUnitsProperty()
    {
        if (! $this->selectedFacilityId) {
            return collect();
        }

        return Unit::where('facility_id', $this->selectedFacilityId)
            ->with('activeLease.tenant')
            ->orderBy('position_y')
            ->orderBy('position_x')
            ->get();
    }

    public function selectUnit(int $unitId): void
    {
        $unit = Unit::with('activeLease.tenant')->find($unitId);

        if (! $unit) {
            $this->selectedUnit = null;
            return;
        }

        $this->selectedUnit = [
            'id' => $unit->id,
            'unit_number' => $unit->unit_number,
            'size' => $unit->size,
            'size_label' => $unit->size_label,
            'monthly_price' => $unit->monthly_price,
            'status' => $unit->status,
            'tenant_name' => $unit->activeLease?->tenant?->full_name,
            'tenant_phone' => $unit->activeLease?->tenant?->phone,
            'move_in_date' => $unit->activeLease?->move_in_date?->format('M d, Y'),
            'monthly_rate' => $unit->activeLease?->monthly_rate,
        ];
    }

    public function closePanel(): void
    {
        $this->selectedUnit = null;
    }
}
