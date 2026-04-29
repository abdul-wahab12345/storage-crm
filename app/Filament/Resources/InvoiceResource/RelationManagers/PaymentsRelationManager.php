<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('amount')
                ->required()
                ->numeric()
                ->prefix(fn () => \App\Models\Setting::currency()),
            Forms\Components\Select::make('method')
                ->options([
                    'cash' => 'Cash',
                    'card' => 'Card',
                    'bank_transfer' => 'Bank Transfer',
                    'check' => 'Check',
                    'other' => 'Other',
                ])
                ->default('cash')
                ->required(),
            Forms\Components\TextInput::make('reference')
                ->placeholder('Check #, transaction ID, etc.'),
            Forms\Components\DateTimePicker::make('paid_at')
                ->default(now())
                ->required(),
            Forms\Components\Textarea::make('notes')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('reference')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('No payments recorded')
            ->emptyStateDescription('Record a payment for this invoice.');
    }
}
