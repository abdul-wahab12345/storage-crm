<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Facility';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'unit_number';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Unit Information')
                ->schema([
                    Forms\Components\Select::make('facility_id')
                        ->relationship('facility', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('unit_number')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('size')
                        ->options([
                            '5x5' => '5x5 (25 sq ft)',
                            '5x10' => '5x10 (50 sq ft)',
                            '10x10' => '10x10 (100 sq ft)',
                            '10x15' => '10x15 (150 sq ft)',
                            '10x20' => '10x20 (200 sq ft)',
                            '10x25' => '10x25 (250 sq ft)',
                            '10x30' => '10x30 (300 sq ft)',
                        ])
                        ->required()
                        ->searchable(),
                    Forms\Components\TextInput::make('size_label')
                        ->placeholder('e.g., Small Closet, Large Garage')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('monthly_price')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0),
                    Forms\Components\Select::make('status')
                        ->options([
                            'available' => 'Available',
                            'occupied' => 'Occupied',
                            'maintenance' => 'Maintenance',
                            'overdue' => 'Overdue',
                        ])
                        ->default('available')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Facility Map Position')
                ->description('Set the grid position for the visual facility map.')
                ->schema([
                    Forms\Components\TextInput::make('position_x')
                        ->label('Column (X)')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('position_y')
                        ->label('Row (Y)')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2)
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
                Tables\Columns\TextColumn::make('unit_number')
                    ->label('Unit #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('facility.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('size')
                    ->sortable(),
                Tables\Columns\TextColumn::make('size_label')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('monthly_price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'info',
                        'maintenance' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('activeLease.tenant.full_name')
                    ->label('Current Tenant')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'maintenance' => 'Maintenance',
                        'overdue' => 'Overdue',
                    ]),
                Tables\Filters\SelectFilter::make('facility')
                    ->relationship('facility', 'name'),
                Tables\Filters\SelectFilter::make('size')
                    ->options([
                        '5x5' => '5x5',
                        '5x10' => '5x10',
                        '10x10' => '10x10',
                        '10x15' => '10x15',
                        '10x20' => '10x20',
                        '10x25' => '10x25',
                        '10x30' => '10x30',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('viewTenant')
                    ->label('View Tenant')
                    ->icon('heroicon-o-user')
                    ->url(fn (Unit $record) => $record->activeLease?->tenant
                        ? TenantResource::getUrl('edit', ['record' => $record->activeLease->tenant])
                        : null
                    )
                    ->visible(fn (Unit $record) => $record->status === 'occupied'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No storage units yet')
            ->emptyStateDescription('Add your first unit to start managing your facility.')
            ->emptyStateIcon('heroicon-o-cube');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
