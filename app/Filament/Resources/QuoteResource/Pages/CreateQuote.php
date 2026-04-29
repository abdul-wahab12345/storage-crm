<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return QuoteResource::computeTotals($data);
    }

    protected function afterCreate(): void
    {
        $this->record->refresh();
        $items = $this->record->items;
        $subtotal  = round($items->sum('total'), 2);
        $taxAmount = round($subtotal * (float) $this->record->tax_rate / 100, 2);

        $this->record->updateQuietly([
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'total'      => round($subtotal + $taxAmount, 2),
        ]);
    }
}
