@php
use App\Models\Setting;

$statusColors = [
    'available'   => ['border'=>'#6ee7b7','bg'=>'#ecfdf5','text'=>'#065f46','badge_bg'=>'#d1fae5','badge_text'=>'#065f46','dot'=>'#10b981'],
    'occupied'    => ['border'=>'#93c5fd','bg'=>'#eff6ff','text'=>'#1e40af','badge_bg'=>'#dbeafe','badge_text'=>'#1e40af','dot'=>'#3b82f6'],
    'maintenance' => ['border'=>'#fcd34d','bg'=>'#fffbeb','text'=>'#92400e','badge_bg'=>'#fef3c7','badge_text'=>'#92400e','dot'=>'#f59e0b'],
    'overdue'     => ['border'=>'#fca5a5','bg'=>'#fef2f2','text'=>'#991b1b','badge_bg'=>'#fecaca','badge_text'=>'#991b1b','dot'=>'#ef4444'],
];
$defaultColor = ['border'=>'#cbd5e1','bg'=>'#f8fafc','text'=>'#475569','badge_bg'=>'#f1f5f9','badge_text'=>'#475569','dot'=>'#94a3b8'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <x-heroicon-o-map class="h-5 w-5 text-primary-500" />
                    <span>Facility Map</span>
                </div>
                <div style="display:flex; align-items:center; gap:12px; font-size:12px;">
                    @foreach($statusColors as $status => $c)
                    <span style="display:inline-flex; align-items:center; gap:5px;">
                        <span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:{{ $c['dot'] }};"></span>
                        {{ ucfirst($status) }}
                    </span>
                    @endforeach
                </div>
            </div>
        </x-slot>

        <div style="display:flex; flex-direction:column; gap:16px;">

            {{-- Facility Selector --}}
            <div style="max-width:280px;">
                <select
                    wire:model.live="selectedFacilityId"
                    class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Select Facility</option>
                    @foreach ($this->facilities as $id => $name)
                        <option value="{{ $id }}" @selected($id == $selectedFacilityId)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Main area: grid + optional detail panel --}}
            <div style="{{ $selectedUnit ? 'display:grid; grid-template-columns:1fr 300px; gap:16px; align-items:start;' : '' }}">

                {{-- Unit Grid --}}
                <div>
                    @if ($this->units->isEmpty())
                        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 0; text-align:center;">
                            <x-heroicon-o-cube class="h-12 w-12 text-gray-400 dark:text-gray-500" style="margin-bottom:12px;" />
                            <p class="text-sm font-medium text-gray-900 dark:text-white">No units found</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400" style="margin-top:4px;">
                                {{ $this->selectedFacilityId ? 'Add units to this facility to see them on the map.' : 'Select a facility to view its units.' }}
                            </p>
                        </div>
                    @else
                        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(70px,1fr)); gap:8px;">
                            @foreach ($this->units as $unit)
                                @php $c = $statusColors[$unit->status] ?? $defaultColor; @endphp
                                <button
                                    wire:click="selectUnit({{ $unit->id }})"
                                    wire:key="unit-btn-{{ $unit->id }}"
                                    title="{{ $unit->unit_number }} — {{ ucfirst($unit->status) }}"
                                    style="
                                        display:flex; flex-direction:column; align-items:center; justify-content:center;
                                        padding:8px 4px; border-radius:8px; min-height:54px;
                                        border:2px solid {{ $c['border'] }};
                                        background:{{ $c['bg'] }};
                                        cursor:pointer;
                                        {{ ($selectedUnit && $selectedUnit['id'] === $unit->id) ? 'outline:2px solid #6366f1; outline-offset:2px;' : '' }}
                                        @if($unit->status === 'overdue') animation:pulse 2s infinite; @endif
                                    "
                                >
                                    <span style="font-size:11px; font-weight:700; line-height:1.2; color:{{ $c['text'] }};">
                                        {{ $unit->unit_number }}
                                    </span>
                                    <span style="font-size:9px; margin-top:2px; opacity:0.7; color:{{ $c['text'] }};">
                                        {{ $unit->size }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Detail Panel --}}
                @if ($selectedUnit)
                @php $sc = $statusColors[$selectedUnit['status']] ?? $defaultColor; @endphp
                <div wire:key="unit-detail-{{ $selectedUnit['id'] }}"
                     class="rounded-xl shadow-sm"
                     style="border:1px solid #e2e8f0; background:#fff; padding:16px;">

                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                        <span style="font-size:15px; font-weight:700; color:#1e293b;">
                            Unit {{ $selectedUnit['unit_number'] }}
                        </span>
                        <button wire:click="closePanel"
                                style="padding:4px; border-radius:50%; border:none; background:transparent; cursor:pointer; color:#94a3b8;"
                                class="hover:bg-gray-100 transition">
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                    </div>

                    {{-- Status badge --}}
                    <div style="margin-bottom:12px;">
                        <span style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; background:{{ $sc['badge_bg'] }}; color:{{ $sc['badge_text'] }};">
                            {{ ucfirst($selectedUnit['status']) }}
                        </span>
                    </div>

                    {{-- Info rows --}}
                    <div style="font-size:13px; display:flex; flex-direction:column; gap:8px;">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:#64748b;">Size</span>
                            <span style="font-weight:600; color:#1e293b;">{{ $selectedUnit['size'] }}</span>
                        </div>
                        @if ($selectedUnit['size_label'])
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:#64748b;">Label</span>
                            <span style="font-weight:600; color:#1e293b;">{{ $selectedUnit['size_label'] }}</span>
                        </div>
                        @endif
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:#64748b;">Price</span>
                            <span style="font-weight:600; color:#1e293b;">{{ Setting::money($selectedUnit['monthly_price']) }}/mo</span>
                        </div>
                    </div>

                    @if ($selectedUnit['tenant_name'])
                    <div style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0;">
                        <p style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#94a3b8; margin-bottom:8px;">
                            Current Tenant
                        </p>
                        <div style="font-size:13px; display:flex; flex-direction:column; gap:8px;">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:#64748b;">Name</span>
                                <span style="font-weight:600; color:#1e293b;">{{ $selectedUnit['tenant_name'] }}</span>
                            </div>
                            @if ($selectedUnit['tenant_phone'])
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:#64748b;">Phone</span>
                                <span style="font-weight:600; color:#1e293b;">{{ $selectedUnit['tenant_phone'] }}</span>
                            </div>
                            @endif
                            @if ($selectedUnit['move_in_date'])
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:#64748b;">Move-in</span>
                                <span style="font-weight:600; color:#1e293b;">{{ $selectedUnit['move_in_date'] }}</span>
                            </div>
                            @endif
                            @if ($selectedUnit['monthly_rate'])
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:#64748b;">Rent</span>
                                <span style="font-weight:600; color:#1e293b;">{{ Setting::money($selectedUnit['monthly_rate']) }}/mo</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0; text-align:center;">
                        <span style="font-size:12px; color:#94a3b8;">No tenant assigned</span>
                    </div>
                    @endif

                </div>
                @endif

            </div>
        </div>

        <style>
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50%       { opacity: 0.6; }
            }
        </style>

    </x-filament::section>
</x-filament-widgets::widget>
