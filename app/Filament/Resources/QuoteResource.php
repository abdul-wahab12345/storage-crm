<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\Pages;
use App\Models\Quote;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'quote_number';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Quote Details')
                ->schema([
                    Forms\Components\TextInput::make('quote_number')
                        ->label('Quote #')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-generated on save.'),
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'sent' => 'Sent',
                            'accepted' => 'Accepted',
                            'rejected' => 'Rejected',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Valid Until'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Client')
                ->schema([
                    Forms\Components\TextInput::make('client_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('client_email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('client_phone')
                        ->tel()
                        ->maxLength(20),
                ])
                ->columns(3),

            Forms\Components\Section::make('Line Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->required()
                                ->placeholder('Product or service description')
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->minValue(0)
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn (Set $set, Get $get, $state) =>
                                    $set('total', round((float) $state * (float) ($get('unit_price') ?? 0), 2))
                                ),
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->prefix(fn () => \App\Models\Setting::currency())
                                ->default(0)
                                ->minValue(0)
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn (Set $set, Get $get, $state) =>
                                    $set('total', round((float) ($get('quantity') ?? 1) * (float) $state, 2))
                                ),
                            Forms\Components\TextInput::make('total')
                                ->label('Line Total')
                                ->numeric()
                                ->prefix(fn () => \App\Models\Setting::currency())
                                ->readOnly()
                                ->dehydrated()
                                ->default(0),
                        ])
                        ->columns(5)
                        ->defaultItems(1)
                        ->addActionLabel('Add Item')
                        ->reorderable('sort_order')
                        ->live(),
                ]),

            // Totals section — Placeholders are reactive and recompute on every Livewire render.
            // Hidden fields carry the computed values through form submission.
            Forms\Components\Section::make('Totals')
                ->schema([
                    Forms\Components\Placeholder::make('subtotal_display')
                        ->label('Subtotal')
                        ->content(fn (Get $get): string =>
                            \App\Models\Setting::money(
                                collect($get('items') ?? [])->sum(fn ($i) => (float) ($i['total'] ?? 0))
                            )
                        ),
                    Forms\Components\TextInput::make('tax_rate')
                        ->label('Tax Rate (%)')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->live(debounce: 500),
                    Forms\Components\Placeholder::make('tax_amount_display')
                        ->label('Tax Amount')
                        ->content(function (Get $get): string {
                            $subtotal = collect($get('items') ?? [])->sum(fn ($i) => (float) ($i['total'] ?? 0));
                            $taxAmount = round($subtotal * (float) ($get('tax_rate') ?? 0) / 100, 2);
                            return \App\Models\Setting::money($taxAmount);
                        }),
                    Forms\Components\Placeholder::make('total_display')
                        ->label('Grand Total')
                        ->content(function (Get $get): \Illuminate\Support\HtmlString {
                            $subtotal = collect($get('items') ?? [])->sum(fn ($i) => (float) ($i['total'] ?? 0));
                            $taxAmount = round($subtotal * (float) ($get('tax_rate') ?? 0) / 100, 2);
                            $sym = \App\Models\Setting::currency();
                            return new \Illuminate\Support\HtmlString(
                                '<span style="font-size:1.1rem;font-weight:700">' . $sym . number_format($subtotal + $taxAmount, 2) . '</span>'
                            );
                        })
                        ->extraAttributes(['class' => 'text-primary-600']),

                    // Hidden fields persisted to DB — filled from mutateFormDataBeforeCreate/Save
                    Forms\Components\Hidden::make('subtotal'),
                    Forms\Components\Hidden::make('tax_amount'),
                    Forms\Components\Hidden::make('total'),
                ])
                ->columns(4),

            Forms\Components\Section::make('Scope of Work')
                ->schema([
                    Forms\Components\RichEditor::make('scope_of_work')
                        ->label('Scope of Work')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'h2', 'h3', 'link'])
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Terms & Conditions')
                ->schema([
                    Forms\Components\RichEditor::make('terms_conditions')
                        ->label('Terms & Conditions')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'h2', 'h3', 'link'])
                        ->default(fn () => Setting::get('quote_terms_conditions'))
                        ->helperText('Pre-filled from Settings → Quote Terms & Conditions. Editable per-quote.')
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    /** Shared computation used by Create and Edit page mutateFormData hooks. */
    public static function computeTotals(array $data): array
    {
        // Recompute each item's total from quantity × unit_price (don't rely on the readonly field)
        $items = collect($data['items'] ?? [])->map(function ($item) {
            $item['total'] = round((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2);
            return $item;
        })->toArray();

        $data['items'] = $items;

        $subtotal = round(collect($items)->sum(fn ($i) => (float) ($i['total'] ?? 0)), 2);
        $taxRate   = (float) ($data['tax_rate'] ?? 0);
        $taxAmount = round($subtotal * $taxRate / 100, 2);

        $data['subtotal']   = $subtotal;
        $data['tax_amount'] = $taxAmount;
        $data['total']      = round($subtotal + $taxAmount, 2);

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_number')
                    ->label('Quote #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('client_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->placeholder('—')
                    ->sortable()
                    ->color(fn (Quote $record) => $record->valid_until && $record->valid_until->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send_email')
                    ->label('Send Email')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->action(function (\App\Models\Quote $record) {
                        if ($record->client_email) {
                            $pdfPath = app(\App\Services\QuotePdfService::class)->generateAndStore($record);
                            
                            $content = "A new quote has been generated for you.\n\n" .
                                       "Quote #: {$record->quote_number}\n" .
                                       "Total: " . \App\Models\Setting::currency() . number_format((float) $record->total, 2);

                            \Illuminate\Support\Facades\Mail::to($record->client_email)->send(
                                new \App\Mail\DocumentMail(
                                    "Quote {$record->quote_number} — StorageCRM",
                                    $content,
                                    \Illuminate\Support\Facades\Storage::disk('local')->path($pdfPath),
                                    "quote-{$record->quote_number}.pdf"
                                )
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Email sent successfully')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Client has no email address')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (Quote $record) => route('quotes.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No quotes yet')
            ->emptyStateDescription('Create your first quote to start sending proposals to clients.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Quote Details')
                ->schema([
                    Infolists\Components\TextEntry::make('quote_number')->label('Quote #'),
                    Infolists\Components\TextEntry::make('title'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'draft' => 'gray',
                            'sent' => 'info',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('valid_until')->date()->placeholder('—'),
                    Infolists\Components\TextEntry::make('client_name'),
                    Infolists\Components\TextEntry::make('client_email')->placeholder('—'),
                    Infolists\Components\TextEntry::make('client_phone')->placeholder('—'),
                    Infolists\Components\TextEntry::make('subtotal')
                        ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state)),
                    Infolists\Components\TextEntry::make('tax_rate')->suffix('%'),
                    Infolists\Components\TextEntry::make('tax_amount')
                        ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state)),
                    Infolists\Components\TextEntry::make('total')
                        ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                        ->weight('bold'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Terms & Conditions')
                ->schema([
                    Infolists\Components\TextEntry::make('terms_conditions')
                        ->label('')
                        ->columnSpanFull()
                        ->prose(),
                ])
                ->collapsible(),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view' => Pages\ViewQuote::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }
}
