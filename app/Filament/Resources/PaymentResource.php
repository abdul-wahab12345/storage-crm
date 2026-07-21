<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Payment Details')
                ->schema([
                    Forms\Components\Select::make('invoice_id')
                        ->relationship('invoice', 'invoice_number')
                        ->required()
                        ->searchable()
                        ->preload(),
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
                    Forms\Components\Textarea::make('notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('invoice.tenant.full_name')
                    ->label('Tenant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('reference')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'bank_transfer' => 'Bank Transfer',
                        'check' => 'Check',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('paid_at')
                    ->form([
                        Forms\Components\DatePicker::make('paid_from'),
                        Forms\Components\DatePicker::make('paid_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['paid_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '>=', $date),
                            )
                            ->when(
                                $data['paid_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['paid_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('From ' . \Carbon\Carbon::parse($data['paid_from'])->toFormattedDateString())
                                ->removeField('paid_from');
                        }
                        if ($data['paid_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Until ' . \Carbon\Carbon::parse($data['paid_until'])->toFormattedDateString())
                                ->removeField('paid_until');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('receipt_pdf')
                    ->label('Receipt PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (Payment $record) => route('payments.receipt.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('paid_at', 'desc')
            ->emptyStateHeading('No payments yet')
            ->emptyStateDescription('Payments are recorded against invoices.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
