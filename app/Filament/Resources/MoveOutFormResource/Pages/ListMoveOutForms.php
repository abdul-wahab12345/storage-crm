<?php

namespace App\Filament\Resources\MoveOutFormResource\Pages;

use App\Filament\Resources\MoveOutFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMoveOutForms extends ListRecords
{
    protected static string $resource = MoveOutFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
