<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-map class="h-5 w-5 text-primary-500" />
                    <span>Facility Map</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-block h-3 w-3 rounded-sm bg-emerald-500"></span> Available
                        <span class="inline-block h-3 w-3 rounded-sm bg-blue-500 ml-2"></span> Occupied
                        <span class="inline-block h-3 w-3 rounded-sm bg-amber-500 ml-2"></span> Maintenance
                        <span class="inline-block h-3 w-3 rounded-sm bg-red-500 ml-2"></span> Overdue
                    </div>
                </div>
            </div>
        </x-slot>

        <div class="space-y-4">
            {{-- Facility Selector --}}
            <div class="max-w-xs">
                <select
                    wire:model.live="selectedFacilityId"
                    class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                >
                    <option value="">Select Facility</option>
                    @foreach ($this->facilities as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Unit Grid --}}
            <div x-data="{ showPanel: @entangle('selectedUnit') }" class="relative">
                @if ($this->units->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <x-heroicon-o-cube class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-3" />
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">No units found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $this->selectedFacilityId ? 'Add units to this facility to see them on the map.' : 'Select a facility to view its units.' }}
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-2">
                        @foreach ($this->units as $unit)
                            <button
                                wire:click="selectUnit({{ $unit->id }})"
                                class="relative group flex flex-col items-center justify-center rounded-lg border-2 p-3 text-center transition-all duration-200 hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                                    @switch($unit->status)
                                        @case('available') border-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:border-emerald-600 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 @break
                                        @case('occupied') border-blue-300 bg-blue-50 hover:bg-blue-100 dark:border-blue-600 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 @break
                                        @case('maintenance') border-amber-300 bg-amber-50 hover:bg-amber-100 dark:border-amber-600 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 @break
                                        @case('overdue') border-red-300 bg-red-50 hover:bg-red-100 dark:border-red-600 dark:bg-red-900/30 dark:hover:bg-red-900/50 animate-pulse @break
                                    @endswitch
                                "
                                title="{{ $unit->unit_number }} — {{ ucfirst($unit->status) }}"
                            >
                                <span class="text-xs font-bold
                                    @switch($unit->status)
                                        @case('available') text-emerald-700 dark:text-emerald-300 @break
                                        @case('occupied') text-blue-700 dark:text-blue-300 @break
                                        @case('maintenance') text-amber-700 dark:text-amber-300 @break
                                        @case('overdue') text-red-700 dark:text-red-300 @break
                                    @endswitch
                                ">
                                    {{ $unit->unit_number }}
                                </span>
                                <span class="text-[10px] opacity-75
                                    @switch($unit->status)
                                        @case('available') text-emerald-600 dark:text-emerald-400 @break
                                        @case('occupied') text-blue-600 dark:text-blue-400 @break
                                        @case('maintenance') text-amber-600 dark:text-amber-400 @break
                                        @case('overdue') text-red-600 dark:text-red-400 @break
                                    @endswitch
                                ">
                                    {{ $unit->size }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Slide-over Panel --}}
                <div
                    x-show="showPanel"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="translate-x-full opacity-0"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="translate-x-0 opacity-100"
                    x-transition:leave-end="translate-x-full opacity-0"
                    class="absolute right-0 top-0 z-10 h-full w-80 overflow-y-auto rounded-lg border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-800"
                    x-cloak
                >
                    @if ($selectedUnit)
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Unit {{ $selectedUnit['unit_number'] }}
                            </h3>
                            <button wire:click="closePanel" class="rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition">
                                <x-heroicon-o-x-mark class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-lg p-3
                                @switch($selectedUnit['status'])
                                    @case('available') bg-emerald-50 dark:bg-emerald-900/30 @break
                                    @case('occupied') bg-blue-50 dark:bg-blue-900/30 @break
                                    @case('maintenance') bg-amber-50 dark:bg-amber-900/30 @break
                                    @case('overdue') bg-red-50 dark:bg-red-900/30 @break
                                @endswitch
                            ">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    @switch($selectedUnit['status'])
                                        @case('available') bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200 @break
                                        @case('occupied') bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200 @break
                                        @case('maintenance') bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-200 @break
                                        @case('overdue') bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200 @break
                                    @endswitch
                                ">
                                    {{ ucfirst($selectedUnit['status']) }}
                                </span>
                            </div>

                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Size</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedUnit['size'] }}</dd>
                                </div>
                                @if ($selectedUnit['size_label'])
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Label</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedUnit['size_label'] }}</dd>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Price</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($selectedUnit['monthly_price'], 2) }}/mo</dd>
                                </div>
                            </dl>

                            @if ($selectedUnit['tenant_name'])
                                <div class="mt-4 border-t border-gray-200 dark:border-gray-600 pt-4">
                                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Current Tenant</h4>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedUnit['tenant_name'] }}</dd>
                                        </div>
                                        @if ($selectedUnit['tenant_phone'])
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                                                <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedUnit['tenant_phone'] }}</dd>
                                            </div>
                                        @endif
                                        @if ($selectedUnit['move_in_date'])
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500 dark:text-gray-400">Move-in</dt>
                                                <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedUnit['move_in_date'] }}</dd>
                                            </div>
                                        @endif
                                        @if ($selectedUnit['monthly_rate'])
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500 dark:text-gray-400">Rent</dt>
                                                <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($selectedUnit['monthly_rate'], 2) }}/mo</dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
