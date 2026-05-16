<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketingCampaignResource\Pages;
use App\Models\MarketingCampaign;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class MarketingCampaignResource extends Resource
{
    protected static ?string $model = MarketingCampaign::class;

    protected static ?string $navigationIcon  = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'WhatsApp Campaigns';
    protected static ?int    $navigationSort  = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        $tenantOptions = Tenant::whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->get()
            ->mapWithKeys(fn ($t) => [$t->id => "{$t->full_name} ({$t->whatsapp_number})"])
            ->toArray();

        return $form->schema([

            Forms\Components\Section::make('Campaign Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Campaign Name')
                        ->placeholder('e.g. May Rent Reminder')
                        ->required(),

                    Forms\Components\TextInput::make('template_name')
                        ->label('WhatsApp Template Name')
                        ->placeholder('e.g. invoice_generated')
                        ->required()
                        ->helperText('Must match the approved template name in your Meta dashboard exactly.'),

                    Forms\Components\Select::make('language_code')
                        ->label('Template Language')
                        ->options([
                            'en_US' => 'English (US)',
                            'en'    => 'English',
                            'ar'    => 'Arabic',
                            'ur'    => 'Urdu',
                        ])
                        ->default('en_US')
                        ->required(),

                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Schedule At (optional)')
                        ->helperText('Leave blank to run manually via: php artisan marketing:send {id}')
                        ->nullable(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Audience')
                ->schema([
                    Forms\Components\Radio::make('audience_type')
                        ->label('Who receives this campaign?')
                        ->options([
                            'all'        => 'All customers with WhatsApp numbers',
                            'all_except' => 'All except selected customers',
                            'selected'   => 'Selected customers only',
                        ])
                        ->default('all')
                        ->live()
                        ->required(),

                    Forms\Components\Select::make('tenant_ids')
                        ->label(fn (Get $get) => $get('audience_type') === 'all_except'
                            ? 'Exclude these customers'
                            : 'Select customers')
                        ->options($tenantOptions)
                        ->multiple()
                        ->searchable()
                        ->visible(fn (Get $get) => in_array($get('audience_type'), ['all_except', 'selected']))
                        ->required(fn (Get $get) => in_array($get('audience_type'), ['all_except', 'selected']))
                        ->helperText('Only customers with a WhatsApp number are listed.'),
                ]),

            Forms\Components\Section::make('Template Variables')
                ->description('Map each template body variable ({{1}}, {{2}}…) to a customer field or static text. Order matches position in template.')
                ->schema([
                    Forms\Components\Repeater::make('body_variables')
                        ->label('')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('Type')
                                ->options([
                                    'field'  => 'Customer field',
                                    'static' => 'Static text',
                                ])
                                ->default('field')
                                ->live()
                                ->required(),

                            Forms\Components\Select::make('value')
                                ->label('Customer field')
                                ->options([
                                    'full_name'       => 'Full Name',
                                    'first_name'      => 'First Name',
                                    'last_name'       => 'Last Name',
                                    'email'           => 'Email',
                                    'phone'           => 'Phone',
                                    'whatsapp_number' => 'WhatsApp Number',
                                    'address'         => 'Address',
                                ])
                                ->visible(fn (Get $get) => $get('type') === 'field')
                                ->required(fn (Get $get) => $get('type') === 'field'),

                            Forms\Components\TextInput::make('value')
                                ->label('Static text')
                                ->visible(fn (Get $get) => $get('type') === 'static')
                                ->required(fn (Get $get) => $get('type') === 'static'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add variable')
                        ->reorderable()
                        ->helperText('First row = {{1}}, second = {{2}}, and so on.'),
                ]),

            Forms\Components\Section::make('Header (optional)')
                ->description('Only fill this if your template has a Document or Image header that requires a URL.')
                ->schema([
                    Forms\Components\TextInput::make('header_url')
                        ->label('Header Document / Image URL')
                        ->url()
                        ->placeholder('https://yoursite.com/file.pdf')
                        ->helperText('Must be a publicly accessible URL. Leave blank if your template has no header or a static header.'),
                ])
                ->collapsible()
                ->collapsed(),

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

                Tables\Columns\TextColumn::make('template_name')
                    ->label('Template')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('audience_type')
                    ->label('Audience')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'all'        => 'All customers',
                        'all_except' => 'All except selected',
                        'selected'   => 'Selected only',
                        default      => $state,
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft'     => 'gray',
                        'sending'   => 'warning',
                        'completed' => 'success',
                        'failed'    => 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent / Total')
                    ->formatStateUsing(fn ($state, MarketingCampaign $record) =>
                        $record->total_count > 0
                            ? "{$record->sent_count} / {$record->total_count}"
                            : '—'
                    ),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state : '—'),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('Manual')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'sending'   => 'Sending',
                        'completed' => 'Completed',
                        'failed'    => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (MarketingCampaign $r) => $r->status === 'draft'),

                Tables\Actions\Action::make('send_now')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (MarketingCampaign $r) => in_array($r->status, ['draft', 'failed']))
                    ->requiresConfirmation()
                    ->modalHeading('Send Campaign')
                    ->modalDescription(fn (MarketingCampaign $r) => "This will send \"{$r->name}\" to all matching recipients immediately. This cannot be undone.")
                    ->action(function (MarketingCampaign $record) {
                        Artisan::call('marketing:send', ['campaign' => $record->id]);
                        $output = Artisan::output();

                        Notification::make()
                            ->title('Campaign dispatched')
                            ->body("Check the campaign record for results.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('dry_run')
                    ->label('Preview Recipients')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (MarketingCampaign $r) => $r->status === 'draft')
                    ->action(function (MarketingCampaign $record) {
                        $count = $record->buildTenantQuery()->count();
                        Notification::make()
                            ->title("Preview: {$count} recipient(s)")
                            ->body("Run: php artisan marketing:send {$record->id} --dry-run to see the full list in your terminal.")
                            ->info()
                            ->send();
                    }),

                Tables\Actions\Action::make('reset')
                    ->label('Reset to Draft')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (MarketingCampaign $r) => in_array($r->status, ['completed', 'failed']))
                    ->requiresConfirmation()
                    ->action(fn (MarketingCampaign $r) => $r->update([
                        'status'       => 'draft',
                        'sent_count'   => 0,
                        'failed_count' => 0,
                        'total_count'  => 0,
                        'started_at'   => null,
                        'completed_at' => null,
                    ])),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (MarketingCampaign $r) => $r->status === 'draft'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No campaigns yet')
            ->emptyStateDescription('Create a campaign to send WhatsApp messages to your customers.')
            ->emptyStateIcon('heroicon-o-megaphone');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMarketingCampaigns::route('/'),
            'create' => Pages\CreateMarketingCampaign::route('/create'),
            'edit'   => Pages\EditMarketingCampaign::route('/{record}/edit'),
        ];
    }
}
