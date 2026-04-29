<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaseResource\Pages;
use App\Models\Lease;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaseResource extends Resource
{
    protected static ?string $model = Lease::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Tenants';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Lease Details')
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->relationship('tenant', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => \App\Models\Tenant::query()
                            ->where(fn ($q) => $q
                                ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                            )
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($t) => [$t->id => "{$t->first_name} {$t->last_name}"])
                        )
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('first_name')->required(),
                            Forms\Components\TextInput::make('last_name')->required(),
                            Forms\Components\TextInput::make('email')->email(),
                            Forms\Components\TextInput::make('phone')->tel(),
                            Forms\Components\TextInput::make('whatsapp_number')
                                ->label('WhatsApp Number')
                                ->tel()
                                ->helperText('Include country code, e.g. +1234567890'),
                        ]),
                    Forms\Components\Select::make('unit_id')
                        ->relationship('unit', 'unit_number', fn ($query) => $query->where('status', 'available'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->unit_number} — {$record->size} (" . \App\Models\Setting::money($record->monthly_price) . "/mo)")
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            if ($state) {
                                $unit = \App\Models\Unit::find($state);
                                if ($unit) {
                                    $set('monthly_rate', $unit->monthly_price);
                                }
                            }
                        }),
                    Forms\Components\DatePicker::make('move_in_date')
                        ->required()
                        ->default(now())
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('billing_day', $state ? Carbon::parse($state)->day : null)),
                    Forms\Components\DatePicker::make('move_out_date'),
                    Forms\Components\TextInput::make('monthly_rate')
                        ->required()
                        ->numeric()
                        ->prefix(fn () => \App\Models\Setting::currency())
                        ->helperText('Pre-filled from unit price. Override if needed.'),
                    Forms\Components\TextInput::make('billing_day')
                        ->required()
                        ->numeric()
                        ->default(now()->day)
                        ->minValue(1)
                        ->maxValue(31)
                        ->helperText('Auto-set from move-in date.'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'terminated' => 'Terminated',
                            'expired' => 'Expired',
                        ])
                        ->default('active')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')->rows(3),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.full_name')
                    ->label('Tenant')
                    ->searchable(['tenant.first_name', 'tenant.last_name'])
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('unit.unit_number')
                    ->label('Unit')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('move_in_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('move_out_date')
                    ->date()
                    ->placeholder('Ongoing'),
                Tables\Columns\TextColumn::make('monthly_rate')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_day')
                    ->label('Bill Day')
                    ->formatStateUsing(fn ($state) => ordinal($state)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'terminated' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'terminated' => 'Terminated',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('terminate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Lease $record) => $record->status === 'active')
                    ->action(function (Lease $record) {
                        $record->update([
                            'status' => 'terminated',
                            'move_out_date' => now(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No leases yet')
            ->emptyStateDescription('Create a lease to link a tenant to a storage unit.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeases::route('/'),
            'create' => Pages\CreateLease::route('/create'),
            'edit' => Pages\EditLease::route('/{record}/edit'),
        ];
    }
}
