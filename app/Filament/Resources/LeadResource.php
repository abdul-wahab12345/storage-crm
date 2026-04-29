<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isSalesman()) {
            $query->where('assigned_to', auth()->id());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Lead Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),
                    Forms\Components\Select::make('source')
                        ->options([
                            'walk_in' => 'Walk-in',
                            'referral' => 'Referral',
                            'online' => 'Online',
                            'phone' => 'Phone',
                            'other' => 'Other',
                        ])
                        ->default('other')
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'new' => 'New',
                            'contacted' => 'Contacted',
                            'qualified' => 'Qualified',
                            'lost' => 'Lost',
                            'converted' => 'Converted',
                        ])
                        ->default('new')
                        ->required(),
                    Forms\Components\TextInput::make('unit_interest')
                        ->label('Unit Interest')
                        ->placeholder('e.g. 10x10, climate controlled')
                        ->maxLength(255),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assigned Salesman')
                        ->options(fn () => User::pluck('name', 'id'))
                        ->default(fn () => auth()->id())
                        ->disabled(fn () => auth()->user()?->isSalesman())
                        ->dehydrated()
                        ->searchable(),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'walk_in' => 'Walk-in',
                        'referral' => 'Referral',
                        'online' => 'Online',
                        'phone' => 'Phone',
                        default => 'Other',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'qualified' => 'success',
                        'lost' => 'danger',
                        'converted' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('unit_interest')
                    ->label('Interest')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isAdmin()),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'lost' => 'Lost',
                        'converted' => 'Converted',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'walk_in' => 'Walk-in',
                        'referral' => 'Referral',
                        'online' => 'Online',
                        'phone' => 'Phone',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned Salesman')
                    ->relationship('assignedUser', 'name')
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No leads yet')
            ->emptyStateDescription('Add your first lead to start tracking your sales pipeline.')
            ->emptyStateIcon('heroicon-o-funnel');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
