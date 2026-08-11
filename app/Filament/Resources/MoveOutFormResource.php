<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MoveOutFormResource\Pages;
use App\Filament\Resources\MoveOutFormResource\RelationManagers;
use App\Models\MoveOutForm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MoveOutFormResource extends Resource
{
    protected static ?string $model = MoveOutForm::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-minus';

    protected static ?string $navigationGroup = 'Tenants';

    protected static ?string $navigationLabel = 'Move Out Forms';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('lease_id')
                    ->relationship('lease', 'id')
                    ->required(),
                Forms\Components\DatePicker::make('move_out_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lease.tenant.full_name')
                    ->label('Tenant Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lease.unit.unit_number')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('move_out_date')
                    ->label('Actual Move Out')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Form Generated On')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->action(fn (MoveOutForm $record) => app(\App\Services\MoveOutFormPdfService::class)->download($record)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMoveOutForms::route('/'),
            'create' => Pages\CreateMoveOutForm::route('/create'),
            'edit' => Pages\EditMoveOutForm::route('/{record}/edit'),
        ];
    }
}
