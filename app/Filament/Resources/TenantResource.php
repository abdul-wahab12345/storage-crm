<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Tenants';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Email' => $record->email,
            'Phone' => $record->phone,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Personal Information')
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('last_name')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('whatsapp_number')
                        ->label('WhatsApp Number')
                        ->tel()
                        ->maxLength(20)
                        ->helperText('Include country code, e.g. +1234567890'),
                    Forms\Components\Textarea::make('address')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Emergency Contact')
                ->schema([
                    Forms\Components\TextInput::make('emergency_contact_name')
                        ->label('Contact Name')
                        ->maxLength(200),
                    Forms\Components\TextInput::make('emergency_contact_phone')
                        ->label('Contact Phone')
                        ->tel()
                        ->maxLength(20),
                ])
                ->columns(2)
                ->collapsible(),

            Forms\Components\Section::make('Documents')
                ->schema([
                    Forms\Components\Repeater::make('documents')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options([
                                    'id_front' => 'ID (Front)',
                                    'id_back' => 'ID (Back)',
                                    'contract' => 'Contract',
                                    'other' => 'Other',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\FileUpload::make('file_path')
                                ->label('File')
                                ->directory('tenant-documents')
                                ->image()
                                ->imageEditor()
                                ->maxSize(5120)
                                ->required(),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add Document')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->rows(3),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name'])
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('active_leases_count')
                    ->label('Active Leases')
                    ->counts('activeLeases')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->label('Balance Due')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(query: function ($query, $direction) {
                        return $query->withSum(
                            ['invoices' => fn ($q) => $q->whereIn('status', ['pending', 'overdue'])],
                            'total'
                        )->orderBy('invoices_sum_total', $direction);
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_active_lease')
                    ->label('Has Active Lease')
                    ->query(fn ($query) => $query->whereHas('activeLeases')),
                Tables\Filters\Filter::make('has_overdue')
                    ->label('Has Overdue Invoices')
                    ->query(fn ($query) => $query->whereHas('invoices', fn ($q) => $q->where('status', 'overdue'))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No tenants yet')
            ->emptyStateDescription('Add your first tenant or import from CSV to get started.')
            ->emptyStateIcon('heroicon-o-users')
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LeasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
