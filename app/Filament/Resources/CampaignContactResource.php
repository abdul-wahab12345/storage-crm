<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignContactResource\Pages;
use App\Filament\Resources\CampaignContactResource\RelationManagers;
use App\Models\CampaignContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignContactResource extends Resource
{
    protected static ?string $model = CampaignContact::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Campaign Contacts';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('whatsapp_number')
                    ->label('WhatsApp Number')
                    ->required()
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->helperText('Include country code, e.g. +1234567890'),
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
                Tables\Columns\TextColumn::make('whatsapp_number')
                    ->label('WhatsApp Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(\App\Filament\Imports\CampaignContactImporter::class)
                    ->color('success'),
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No contacts yet')
            ->emptyStateDescription('Import a CSV of contacts or add them manually to use in WhatsApp campaigns.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCampaignContacts::route('/'),
        ];
    }
}
