<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Imports\TenantsImport;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    Forms\Components\FileUpload::make('csv_file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->directory('csv-imports')
                        ->required(),
                    Forms\Components\Placeholder::make('instructions')
                        ->content('CSV must have headers: first_name, last_name, email, phone, address, emergency_contact_name, emergency_contact_phone, notes')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    try {
                        $path = storage_path('app/public/' . $data['csv_file']);
                        Excel::import(new TenantsImport, $path);

                        Notification::make()
                            ->title('Import Successful')
                            ->body('Tenants have been imported from CSV.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
