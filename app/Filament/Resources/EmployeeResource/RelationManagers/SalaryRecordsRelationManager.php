<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalaryRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'salaryRecords';

    protected static ?string $title = 'Salary Records';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('month')
                ->options([
                    1 => 'January', 2 => 'February', 3 => 'March',
                    4 => 'April', 5 => 'May', 6 => 'June',
                    7 => 'July', 8 => 'August', 9 => 'September',
                    10 => 'October', 11 => 'November', 12 => 'December',
                ])
                ->required(),
            Forms\Components\TextInput::make('year')
                ->numeric()
                ->default(now()->year)
                ->minValue(2000)
                ->required(),
            Forms\Components\TextInput::make('base_salary')
                ->label('Base Salary')
                ->numeric()
                ->prefix(fn () => \App\Models\Setting::currency())
                ->default(fn () => $this->getOwnerRecord()->base_salary)
                ->live(debounce: 500)
                ->afterStateUpdated(fn (Set $set, Get $get, $state) =>
                    $set('total', round((float) $state + (float) ($get('bonuses') ?? 0) - (float) ($get('deductions') ?? 0), 2))
                )
                ->required(),
            Forms\Components\TextInput::make('bonuses')
                ->numeric()
                ->prefix(fn () => \App\Models\Setting::currency())
                ->default(0)
                ->live(debounce: 500)
                ->afterStateUpdated(fn (Set $set, Get $get, $state) =>
                    $set('total', round((float) ($get('base_salary') ?? 0) + (float) $state - (float) ($get('deductions') ?? 0), 2))
                ),
            Forms\Components\TextInput::make('deductions')
                ->numeric()
                ->prefix(fn () => \App\Models\Setting::currency())
                ->default(0)
                ->live(debounce: 500)
                ->afterStateUpdated(fn (Set $set, Get $get, $state) =>
                    $set('total', round((float) ($get('base_salary') ?? 0) + (float) ($get('bonuses') ?? 0) - (float) $state, 2))
                ),
            Forms\Components\TextInput::make('total')
                ->label('Net Total')
                ->numeric()
                ->prefix(fn () => \App\Models\Setting::currency())
                ->readOnly()
                ->dehydrated()
                ->default(0),
            Forms\Components\Select::make('status')
                ->options(['pending' => 'Pending', 'paid' => 'Paid'])
                ->default('pending')
                ->required(),
            Forms\Components\DatePicker::make('paid_at')
                ->label('Paid On'),
            Forms\Components\Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('month_label')
            ->columns([
                Tables\Columns\TextColumn::make('month_label')
                    ->label('Period')
                    ->sortable(['year', 'month'])
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('base_salary')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state)),
                Tables\Columns\TextColumn::make('bonuses')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->color('success'),
                Tables\Columns\TextColumn::make('deductions')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->color('danger'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Net Pay')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'paid' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->date()
                    ->placeholder('—'),
            ])
            ->defaultSort('year', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add Salary Record'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'paid', 'paid_at' => now()])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
