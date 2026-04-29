<x-filament-panels::page>
    {{-- Revenue Report --}}
    <x-filament::section>
        <x-slot name="heading">Revenue Report</x-slot>
        <x-slot name="description">Monthly revenue from paid invoices.</x-slot>

        <div class="flex flex-wrap items-end gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year</label>
                <select wire:model.live="revenueYear"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                    @foreach ($this->getAvailableYears() as $y => $label)
                        <option value="{{ $y }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-filament::button
                wire:click="exportRevenue"
                color="gray"
                icon="heroicon-o-arrow-down-tray"
                size="sm">
                Export Excel
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ route('reports.revenue.pdf') }}"
                target="_blank"
                color="danger"
                icon="heroicon-o-document-arrow-down"
                size="sm">
                Export PDF
            </x-filament::button>
        </div>

        @php $chart = $this->getRevenueChartData(); @endphp

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach ($chart['labels'] as $i => $label)
                @php $value = $chart['values'][$i]; $max = max($chart['values']) ?: 1; @endphp
                <div class="flex flex-col items-center">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        {{ \App\Models\Setting::money($value, 0) }}
                    </span>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-t" style="height: 80px; display: flex; align-items: flex-end;">
                        <div class="w-full rounded-t bg-primary-500"
                            style="height: {{ $max > 0 ? round(($value / $max) * 100) : 0 }}%;"></div>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            <strong>Total for {{ $revenueYear }}:</strong>
            {{ \App\Models\Setting::money(array_sum($chart['values'])) }}
        </div>
    </x-filament::section>

    {{-- Payments Collected --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Payments Collected</x-slot>
        <x-slot name="description">Breakdown by payment method for the selected month.</x-slot>

        <div class="flex flex-wrap items-end gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Month</label>
                <input type="month" wire:model.live="paymentsMonth"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
            </div>
            <x-filament::button
                wire:click="exportPayments"
                color="gray"
                icon="heroicon-o-arrow-down-tray"
                size="sm">
                Export Excel
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ route('reports.payments.pdf', ['month' => $paymentsMonth]) }}"
                target="_blank"
                color="danger"
                icon="heroicon-o-document-arrow-down"
                size="sm">
                Export PDF
            </x-filament::button>
        </div>

        @php $payments = $this->getPaymentBreakdown(); @endphp
        @if ($payments->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">No payments for this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300">Method</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-right">Count</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 capitalize">{{ str_replace('_', ' ', $row->method) }}</td>
                                <td class="py-2 text-right">{{ $row->count }}</td>
                                <td class="py-2 text-right font-medium">{{ \App\Models\Setting::money($row->total) }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-bold">
                            <td class="pt-2">Total</td>
                            <td class="pt-2 text-right">{{ $payments->sum('count') }}</td>
                            <td class="pt-2 text-right">{{ \App\Models\Setting::money($payments->sum('total')) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- Overdue Invoices --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Overdue Invoices</x-slot>
        <x-slot name="description">All currently overdue invoices requiring attention.</x-slot>

        <div class="flex justify-end mb-4 gap-2">
            <x-filament::button
                wire:click="exportOverdue"
                color="gray"
                icon="heroicon-o-arrow-down-tray"
                size="sm">
                Export Excel
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ route('reports.overdue.pdf') }}"
                target="_blank"
                color="danger"
                icon="heroicon-o-document-arrow-down"
                size="sm">
                Export PDF
            </x-filament::button>
        </div>

        @php $overdue = $this->getOverdueInvoices(); @endphp
        @if ($overdue->isEmpty())
            <p class="text-green-600 dark:text-green-400 text-sm font-medium">No overdue invoices.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300">Invoice #</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300">Tenant</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300">Unit</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-right">Amount</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300">Due Date</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-right">Days Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($overdue as $invoice)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 font-medium">{{ $invoice->invoice_number }}</td>
                                <td class="py-2">{{ $invoice->tenant?->full_name }}</td>
                                <td class="py-2">{{ $invoice->lease?->unit?->unit_number ?? '—' }}</td>
                                <td class="py-2 text-right text-danger-600 font-medium">{{ \App\Models\Setting::money($invoice->total) }}</td>
                                <td class="py-2">{{ $invoice->due_date?->format('M j, Y') }}</td>
                                <td class="py-2 text-right text-danger-600">{{ abs((int) now()->diffInDays($invoice->due_date)) }}d</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- Salesman Performance --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Salesman Performance</x-slot>
        <x-slot name="description">Lead pipeline breakdown per salesman.</x-slot>

        <div class="flex justify-end mb-4 gap-2">
            <x-filament::button
                wire:click="exportSalesman"
                color="gray"
                icon="heroicon-o-arrow-down-tray"
                size="sm">
                Export Excel
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="{{ route('reports.salesman.pdf') }}"
                target="_blank"
                color="danger"
                icon="heroicon-o-document-arrow-down"
                size="sm">
                Export PDF
            </x-filament::button>
        </div>

        @php $salesmen = $this->getSalesmanStats(); @endphp
        @if ($salesmen->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">No salesman accounts found.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300">Salesman</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-center">Total</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-center">New</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-center">Contacted</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-center">Qualified</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-center">Converted</th>
                            <th class="pb-2 font-semibold text-gray-700 dark:text-gray-300 text-center">Lost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($salesmen as $s)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 font-medium">{{ $s->name }}</td>
                                <td class="py-2 text-center font-bold">{{ $s->leads_total }}</td>
                                <td class="py-2 text-center">{{ $s->leads_new }}</td>
                                <td class="py-2 text-center">{{ $s->leads_contacted }}</td>
                                <td class="py-2 text-center text-success-600">{{ $s->leads_qualified }}</td>
                                <td class="py-2 text-center text-primary-600 font-medium">{{ $s->leads_converted }}</td>
                                <td class="py-2 text-center text-danger-600">{{ $s->leads_lost }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
