<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LeasesRelationManager extends RelationManager
{
    protected static string $relationship = 'leases';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('unit_id')
                ->relationship('unit', 'unit_number')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\DatePicker::make('move_in_date')
                ->required()
                ->live()
                ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('billing_day', $state ? \Carbon\Carbon::parse($state)->day : null)),
            Forms\Components\DatePicker::make('move_out_date'),
            Forms\Components\TextInput::make('monthly_rate')
                ->numeric()
                ->prefix('$')
                ->required(),
            Forms\Components\TextInput::make('billing_day')
                ->numeric()
                ->minValue(1)
                ->maxValue(31)
                ->helperText('Auto-set from move-in date. The day each month when rent is due.'),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'terminated' => 'Terminated',
                    'expired' => 'Expired',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('unit.unit_number')
                    ->label('Unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('move_in_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('move_out_date')
                    ->date()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('monthly_rate')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('billing_day')
                    ->label('Bill Day')
                    ->suffix(fn ($state) => match (true) {
                        in_array($state % 10, [1]) && $state !== 11 => 'st',
                        in_array($state % 10, [2]) && $state !== 12 => 'nd',
                        in_array($state % 10, [3]) && $state !== 13 => 'rd',
                        default => 'th',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'terminated' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('No leases')
            ->emptyStateDescription('Create a lease to assign a storage unit to this tenant.');
    }
}
