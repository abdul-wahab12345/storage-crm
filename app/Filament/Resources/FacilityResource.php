<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacilityResource\Pages;
use App\Models\Facility;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FacilityResource extends Resource
{
    protected static ?string $model = Facility::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Facility Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('address')
                        ->rows(3),
                    Forms\Components\TextInput::make('phone')
                        ->tel(),
                    Forms\Components\TextInput::make('email')
                        ->email(),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Late Fee Configuration')
                ->description('Default late fee settings applied to overdue invoices for this facility.')
                ->schema([
                    Forms\Components\Select::make('late_fee_type')
                        ->options([
                            'flat' => 'Flat Fee',
                            'percentage' => 'Percentage of Rent (%)',
                        ])
                        ->default('flat')
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('late_fee_amount')
                        ->numeric()
                        ->prefix(fn (Forms\Get $get) => $get('late_fee_type') === 'percentage' ? '%' : \App\Models\Setting::currency())
                        ->default(0)
                        ->required(),
                    Forms\Components\TextInput::make('late_fee_grace_days')
                        ->label('Grace Period (days)')
                        ->numeric()
                        ->default(5)
                        ->helperText('Number of days after due date before late fee applies.')
                        ->required(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Webhook Integration')
                ->description('Configure the n8n webhook URL for WhatsApp notifications.')
                ->schema([
                    Forms\Components\TextInput::make('webhook_url')
                        ->label('n8n Webhook URL')
                        ->url()
                        ->placeholder('https://your-n8n-instance.com/webhook/...')
                        ->helperText('The system will POST invoice data to this URL when invoices are generated.'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('units_count')
                    ->label('Units')
                    ->counts('units')
                    ->sortable(),
                Tables\Columns\TextColumn::make('late_fee_type')
                    ->label('Late Fee')
                    ->formatStateUsing(fn (Facility $record) => $record->late_fee_type === 'flat'
                        ? \App\Models\Setting::money($record->late_fee_amount)
                        : $record->late_fee_amount . '%'
                    ),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No facilities yet')
            ->emptyStateDescription('Create your first storage facility to get started.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacilities::route('/'),
            'create' => Pages\CreateFacility::route('/create'),
            'edit' => Pages\EditFacility::route('/{record}/edit'),
        ];
    }
}
