<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Expense Details')->schema([
                    Forms\Components\Select::make('category')
                        ->options([
                            'rent' => 'Rent',
                            'utilities' => 'Utilities',
                            'maintenance' => 'Maintenance',
                            'supplies' => 'Supplies',
                            'marketing' => 'Marketing',
                            'payroll' => 'Payroll',
                            'other' => 'Other',
                        ])
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('description')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix(fn () => \App\Models\Setting::currency()),
                    Forms\Components\DatePicker::make('expense_date')
                        ->required()
                        ->default(now()),
                    Forms\Components\Select::make('facility_id')
                        ->relationship('facility', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('salary_record_id')
                        ->relationship('salaryRecord', 'id') // Ideally this would show a nice label but salary record doesn't have a name
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->employee->full_name . ' - ' . $record->month_label)
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get) => $get('category') === 'payroll'),
                    Forms\Components\TextInput::make('vendor')
                        ->maxLength(150),
                    Forms\Components\FileUpload::make('receipt_path')
                        ->label('Receipt')
                        ->directory('receipts')
                        ->openable()
                        ->downloadable(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'paid' => 'Paid',
                        ])
                        ->required()
                        ->default('pending'),
                    Forms\Components\DatePicker::make('paid_at'),
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn () => \App\Models\Setting::currency())
                    ->sortable(),
                Tables\Columns\TextColumn::make('facility.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($record) => $record->status_color),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'rent' => 'Rent',
                        'utilities' => 'Utilities',
                        'maintenance' => 'Maintenance',
                        'supplies' => 'Supplies',
                        'marketing' => 'Marketing',
                        'payroll' => 'Payroll',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                    ]),
                Tables\Filters\Filter::make('expense_date')
                    ->form([
                        Forms\Components\DatePicker::make('expense_from'),
                        Forms\Components\DatePicker::make('expense_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['expense_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('expense_date', '>=', $date),
                            )
                            ->when(
                                $data['expense_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('expense_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['expense_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('From ' . \Carbon\Carbon::parse($data['expense_from'])->toFormattedDateString())
                                ->removeField('expense_from');
                        }
                        if ($data['expense_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Until ' . \Carbon\Carbon::parse($data['expense_until'])->toFormattedDateString())
                                ->removeField('expense_until');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsApproved')
                        ->label('Mark as Approved')
                        ->icon('heroicon-o-check')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['status' => 'approved']))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('markAsPaid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-currency-dollar')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['status' => 'paid', 'paid_at' => now()]))
                        ->requiresConfirmation(),
                ]),
            ]);
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
