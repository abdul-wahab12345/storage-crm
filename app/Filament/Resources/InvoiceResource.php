<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'overdue')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Invoice Details')
                ->schema([
                    Forms\Components\Select::make('lease_id')
                        ->relationship('lease', 'id')
                        ->getOptionLabelFromRecordUsing(fn($record) => "Lease #{$record->id} — {$record->tenant->full_name} (Unit {$record->unit->unit_number})")
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(
                            fn(string $search) => \App\Models\Lease::query()
                                ->with(['tenant', 'unit'])
                                ->whereHas(
                                    'tenant',
                                    fn($q) => $q
                                        ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                        ->orWhere('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                )
                                ->orWhere('id', 'like', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn($l) => [$l->id => "Lease #{$l->id} — {$l->tenant->full_name} (Unit {$l->unit->unit_number})"])
                        )
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            if ($state) {
                                $lease = \App\Models\Lease::with('tenant')->find($state);
                                if ($lease) {
                                    $set('tenant_id', $lease->tenant_id);
                                    $set('amount', $lease->monthly_rate);
                                    $set('total', $lease->monthly_rate);
                                }
                            }
                        }),
                    Forms\Components\Hidden::make('tenant_id'),
                    Forms\Components\TextInput::make('invoice_number')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-generated if left empty.'),
                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix(fn() => \App\Models\Setting::currency())
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                            $base = (float) ($get('amount') ?? 0);
                            $late = (float) ($get('custom_late_fee') ?? $get('late_fee') ?? 0);
                            $additional = 0;
                            foreach ($get('additional_fees') ?? [] as $fee) {
                                $additional += (float) ($fee['amount'] ?? 0);
                            }
                            $set('total', $base + $late + $additional);
                        }),
                    Forms\Components\TextInput::make('late_fee')
                        ->numeric()
                        ->prefix(fn() => \App\Models\Setting::currency())
                        ->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                            $base = (float) ($get('amount') ?? 0);
                            $late = (float) ($get('custom_late_fee') ?? $get('late_fee') ?? 0);
                            $additional = 0;
                            foreach ($get('additional_fees') ?? [] as $fee) {
                                $additional += (float) ($fee['amount'] ?? 0);
                            }
                            $set('total', $base + $late + $additional);
                        }),
                    Forms\Components\TextInput::make('custom_late_fee')
                        ->label('Custom Late Fee Override')
                        ->numeric()
                        ->prefix(fn() => \App\Models\Setting::currency())
                        ->helperText('Overrides facility default for this invoice only.')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                            $base = (float) ($get('amount') ?? 0);
                            $late = (float) ($get('custom_late_fee') ?? $get('late_fee') ?? 0);
                            $additional = 0;
                            foreach ($get('additional_fees') ?? [] as $fee) {
                                $additional += (float) ($fee['amount'] ?? 0);
                            }
                            $set('total', $base + $late + $additional);
                        }),
                    Forms\Components\TextInput::make('total')
                        ->required()
                        ->numeric()
                        ->prefix(fn() => \App\Models\Setting::currency())
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\DatePicker::make('due_date')
                        ->required()
                        ->default(now()),
                    Forms\Components\DatePicker::make('period_start')
                        ->required()
                        ->default(now()),
                    Forms\Components\DatePicker::make('period_end')
                        ->required()
                        ->default(now()->addMonth()),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'overdue' => 'Overdue',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')->rows(3),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Additional Fees / Services')
                ->schema([
                    Forms\Components\Repeater::make('additional_fees')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('amount')
                                ->required()
                                ->numeric()
                                ->prefix(fn() => \App\Models\Setting::currency())
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    $base = (float) ($get('../../amount') ?? 0);
                                    $late = (float) ($get('../../custom_late_fee') ?? $get('../../late_fee') ?? 0);
                                    $additional = 0;
                                    foreach ($get('../../additional_fees') ?? [] as $fee) {
                                        $additional += (float) ($fee['amount'] ?? 0);
                                    }
                                    $set('../../total', $base + $late + $additional);
                                }),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add Fee / Service')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                            $base = (float) ($get('amount') ?? 0);
                            $late = (float) ($get('custom_late_fee') ?? $get('late_fee') ?? 0);
                            $additional = 0;
                            foreach ($get('additional_fees') ?? [] as $fee) {
                                $additional += (float) ($fee['amount'] ?? 0);
                            }
                            $set('total', $base + $late + $additional);
                        }),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('tenant.full_name')
                    ->label('Tenant')
                    ->searchable(['tenant.first_name', 'tenant.last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('lease.unit.unit_number')
                    ->label('Unit'),
                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn($state) => \App\Models\Setting::money($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('late_fee')
                    ->formatStateUsing(fn($state) => \App\Models\Setting::money($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn($state) => \App\Models\Setting::money($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn(Invoice $record) => $record->is_overdue ? 'danger' : null),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn($query) => $query->where('status', 'overdue')),
                Tables\Filters\Filter::make('due_this_month')
                    ->label('Due This Month')
                    ->query(fn($query) => $query->whereMonth('due_date', now()->month)->whereYear('due_date', now()->year)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->isAdmin()),
                Tables\Actions\Action::make('send_email')
                    ->label('Send Email')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->action(function (\App\Models\Invoice $record) {
                        if ($record->tenant->email) {
                            $record->tenant->notify(new \App\Notifications\InvoiceGeneratedNotification($record));
                            \Filament\Notifications\Notification::make()
                                ->title('Email sent successfully')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Tenant has no email address')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (\App\Models\Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn() => auth()->user()?->isAdmin()),
                Tables\Actions\Action::make('recordPayment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(Invoice $record) => auth()->user()?->isAdmin() && in_array($record->status, ['pending', 'overdue']))
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix(fn() => \App\Models\Setting::currency())
                            ->default(fn(Invoice $record) => $record->balance_due),
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
                    ])
                    ->action(function (Invoice $record, array $data) {
                        $record->payments()->create($data);
                    }),
                Tables\Actions\Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn(Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('sendNotification')
                    ->label('Send Notification')
                    ->icon('heroicon-o-bell')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice Notification')
                    ->modalDescription('This will send an email and WhatsApp message (if configured) to the tenant for this invoice.')
                    ->visible(fn() => auth()->user()?->isAdmin())
                    ->action(function (Invoice $record) {
                        if ($record->tenant) {
                            $record->tenant->notify(new \App\Notifications\InvoiceGeneratedNotification($record));
                            \Filament\Notifications\Notification::make()
                                ->title('Notification sent')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No invoices yet')
            ->emptyStateDescription('Invoices are auto-generated by the billing engine, or create one manually.')
            ->emptyStateIcon('heroicon-o-document-currency-dollar');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Invoice Details')
                ->schema([
                    Infolists\Components\TextEntry::make('invoice_number')->label('Invoice #'),
                    Infolists\Components\TextEntry::make('tenant.full_name')->label('Tenant'),
                    Infolists\Components\TextEntry::make('lease.unit.unit_number')->label('Unit'),
                    Infolists\Components\TextEntry::make('amount')
                        ->formatStateUsing(fn($state) => \App\Models\Setting::money($state)),
                    Infolists\Components\TextEntry::make('late_fee')
                        ->formatStateUsing(fn($state) => \App\Models\Setting::money($state)),
                    Infolists\Components\TextEntry::make('total')
                        ->formatStateUsing(fn($state) => \App\Models\Setting::money($state))
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('due_date')->date(),
                    Infolists\Components\TextEntry::make('period_start')->date(),
                    Infolists\Components\TextEntry::make('period_end')->date(),
                    Infolists\Components\TextEntry::make('status')->badge()->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                    Infolists\Components\TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                ])
                ->columns(3),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
