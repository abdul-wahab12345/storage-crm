<?php

namespace App\Filament\Resources\MoveOutFormResource\Pages;

use App\Filament\Resources\MoveOutFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMoveOutForm extends EditRecord
{
    protected static string $resource = MoveOutFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
