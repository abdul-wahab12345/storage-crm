<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Event')
                ->schema([
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Timestamp')
                        ->dateTime('F j, Y — g:i:s A'),
                    Infolists\Components\TextEntry::make('action')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'created' => 'success',
                            'updated' => 'warning',
                            'deleted' => 'danger',
                            default   => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('model_type')->label('Model'),
                    Infolists\Components\TextEntry::make('model_id')->label('Record ID'),
                    Infolists\Components\TextEntry::make('user_name')->label('User'),
                    Infolists\Components\TextEntry::make('ip_address')->label('IP Address')->placeholder('—'),
                ])
                ->columns(2),

            Infolists\Components\Section::make('Changes')
                ->schema([
                    Infolists\Components\TextEntry::make('changes_html')
                        ->label('')
                        ->getStateUsing(fn (ActivityLog $record): string => static::renderChanges($record))
                        ->html()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected static function stringify(mixed $value): string
    {
        if (is_null($value))  return '<em>(null)</em>';
        if (is_bool($value))  return $value ? 'true' : 'false';
        if (is_array($value)) return '<code>' . e(json_encode($value, JSON_PRETTY_PRINT)) . '</code>';
        return e((string) $value);
    }

    protected static function renderChanges(ActivityLog $record): string
    {
        $changes = $record->changes;

        if (empty($changes)) {
            return match ($record->action) {
                'created' => '<span class="text-gray-500">Record was created.</span>',
                'deleted' => '<span class="text-gray-500">Record was deleted.</span>',
                default   => '<span class="text-gray-500">No changes recorded.</span>',
            };
        }

        if ($record->action === 'updated') {
            $new = $changes['new'] ?? [];
            $old = $changes['old'] ?? [];

            if (empty($new)) {
                return '<span class="text-gray-500">No meaningful fields changed.</span>';
            }

            $rows = '';
            foreach ($new as $field => $value) {
                $label = e(ucwords(str_replace('_', ' ', $field)));
                $prev  = static::stringify($old[$field] ?? null);
                $curr  = static::stringify($value);
                $rows .= "
                    <tr>
                        <td style='padding:6px 12px 6px 0;font-weight:600;color:#64748b;white-space:nowrap;vertical-align:top'>{$label}</td>
                        <td style='padding:6px 8px;color:#dc2626;vertical-align:top'>{$prev}</td>
                        <td style='padding:6px 8px;color:#64748b;vertical-align:top'>→</td>
                        <td style='padding:6px 0 6px 8px;color:#16a34a;vertical-align:top'>{$curr}</td>
                    </tr>";
            }

            return "<table style='width:100%;border-collapse:collapse;font-size:13px'>{$rows}</table>";
        }

        // created / deleted — flat key-value list
        $rows = '';
        foreach ($changes as $field => $value) {
            if (in_array($field, ['created_at', 'updated_at'])) {
                continue;
            }
            $label = e(ucwords(str_replace('_', ' ', $field)));
            $val   = static::stringify($value);
            $rows .= "
                <tr>
                    <td style='padding:5px 12px 5px 0;font-weight:600;color:#64748b;white-space:nowrap;vertical-align:top'>{$label}</td>
                    <td style='padding:5px 0;vertical-align:top'>{$val}</td>
                </tr>";
        }

        return $rows
            ? "<table style='width:100%;border-collapse:collapse;font-size:13px'>{$rows}</table>"
            : '<span class="text-gray-500">No data recorded.</span>';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('F j, Y g:i:s A')),

                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('model_type')
                    ->label('Model')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('model_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('changes')
                    ->label('Changes')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '—';
                        }
                        if (is_array($state) && isset($state['new'])) {
                            $fields = array_keys($state['new']);
                            return implode(', ', $fields);
                        }
                        return '—';
                    })
                    ->tooltip(function ($record) {
                        if (empty($record->changes)) {
                            return null;
                        }
                        $new = $record->changes['new'] ?? [];
                        $old = $record->changes['old'] ?? [];
                        $lines = [];
                        foreach ($new as $field => $value) {
                            $prev  = $old[$field] ?? null;
                            $prev  = is_array($prev)  ? json_encode($prev)  : (string) ($prev  ?? '(empty)');
                            $value = is_array($value) ? json_encode($value) : (string) ($value ?? '(null)');
                            $lines[] = "{$field}: {$prev} → {$value}";
                        }
                        return implode("\n", $lines);
                    })
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),

                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Model')
                    ->options(
                        ActivityLog::query()
                            ->distinct()
                            ->pluck('model_type', 'model_type')
                            ->toArray()
                    ),

                Tables\Filters\Filter::make('today')
                    ->label('Today only')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->slideOver(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No activity logs yet')
            ->emptyStateDescription('Actions on models will appear here automatically.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
