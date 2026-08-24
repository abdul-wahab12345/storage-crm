<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'HR';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Personal Information')
                ->schema([
                    Forms\Components\TextInput::make('first_name')->required()->maxLength(100),
                    Forms\Components\TextInput::make('last_name')->required()->maxLength(100),
                    Forms\Components\TextInput::make('email')->email()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                    Forms\Components\TextInput::make('emirates_id')
                        ->label('Emirates ID')
                        ->mask('999-9999-9999999-9')
                        ->maxLength(100),
                ])
                ->columns(2),

            Forms\Components\Section::make('Employment Details')
                ->schema([
                    Forms\Components\TextInput::make('position')
                        ->required()
                        ->maxLength(150),
                    Forms\Components\TextInput::make('department')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('base_salary')
                        ->label('Base Salary')
                        ->numeric()
                        ->prefix(fn () => \App\Models\Setting::currency())
                        ->required()
                        ->minValue(0),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active'   => 'Active',
                            'inactive' => 'Inactive',
                            'on_leave' => 'On Leave',
                        ])
                        ->default('active')
                        ->required(),
                    Forms\Components\DatePicker::make('join_date')
                        ->required()
                        ->default(now()),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->placeholder('Only set if no longer employed'),
                ])
                ->columns(3),

            Forms\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')->rows(3),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Documents')
                ->schema([
                    Forms\Components\FileUpload::make('documents')
                        ->multiple()
                        ->directory('employee-documents')
                        ->preserveFilenames()
                        ->reorderable()
                        ->appendFiles()
                        ->downloadable(),
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
                Tables\Columns\TextColumn::make('position')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Base Salary')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'gray',
                        'on_leave' => 'warning',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('join_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salary_records_count')
                    ->label('Salary Records')
                    ->counts('salaryRecords')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                        'on_leave' => 'On Leave',
                    ]),
                Tables\Filters\SelectFilter::make('department')
                    ->options(fn () => Employee::distinct()->whereNotNull('department')->pluck('department', 'department')->toArray()),
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
            ->defaultSort('first_name')
            ->emptyStateHeading('No employees yet')
            ->emptyStateIcon('heroicon-o-identification');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Employee Details')
                ->schema([
                    Infolists\Components\TextEntry::make('full_name')->label('Name'),
                    Infolists\Components\TextEntry::make('email')->placeholder('—'),
                    Infolists\Components\TextEntry::make('phone')->placeholder('—'),
                    Infolists\Components\TextEntry::make('position'),
                    Infolists\Components\TextEntry::make('department')->placeholder('—'),
                    Infolists\Components\TextEntry::make('base_salary')
                        ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state)),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'active'   => 'success',
                            'inactive' => 'gray',
                            'on_leave' => 'warning',
                            default    => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('join_date')->date(),
                    Infolists\Components\TextEntry::make('end_date')->date()->placeholder('—'),
                ])
                ->columns(3),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SalaryRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view'   => Pages\ViewEmployee::route('/{record}'),
            'edit'   => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
