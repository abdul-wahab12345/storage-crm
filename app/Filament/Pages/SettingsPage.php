<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Application Settings';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.settings-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'terms_conditions' => Setting::get('terms_conditions'),
            'agreement_terms_conditions' => Setting::get('agreement_terms_conditions'),
            'quote_terms_conditions' => Setting::get('quote_terms_conditions'),
            'company_name' => Setting::get('company_name'),
            'company_address' => Setting::get('company_address'),
            'company_phone' => Setting::get('company_phone'),
            'company_email' => Setting::get('company_email'),
            'currency' => Setting::get('currency', 'USD'),
            'currency_symbol' => Setting::get('currency_symbol', '$'),
            'reminder_days_before' => Setting::get('reminder_days_before', '7,3,1'),
            'reminder_whatsapp_template' => Setting::get('reminder_whatsapp_template', 'payment_reminder'),
            'reminder_whatsapp_language' => Setting::get('reminder_whatsapp_language', 'en_US'),
            'admin_notification_email' => Setting::get('admin_notification_email'),
            'admin_whatsapp_phone' => Setting::get('admin_whatsapp_phone'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Information')
                    ->description('Used on invoices and quotes.')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company_email')
                            ->label('Company Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company_phone')
                            ->label('Company Phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Textarea::make('company_address')
                            ->label('Company Address')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Currency')
                    ->description('Currency used on invoices, quotes, and salary records.')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD — US Dollar ($)',
                                'EUR' => 'EUR — Euro (€)',
                                'GBP' => 'GBP — British Pound (£)',
                                'PKR' => 'PKR — Pakistani Rupee (PKR)',
                                'SAR' => 'SAR — Saudi Riyal (SAR)',
                                'AED' => 'AED — UAE Dirham (AED)',
                                'INR' => 'INR — Indian Rupee (₹)',
                                'CAD' => 'CAD — Canadian Dollar (CA$)',
                                'AUD' => 'AUD — Australian Dollar (A$)',
                                'OTHER' => 'Other (set symbol manually)',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $map = [
                                    'USD' => '$', 'EUR' => '€', 'GBP' => '£',
                                    'PKR' => 'PKR ', 'SAR' => 'SAR ', 'AED' => 'AED ',
                                    'INR' => '₹', 'CAD' => 'CA$', 'AUD' => 'A$',
                                ];
                                if (isset($map[$state])) {
                                    $set('currency_symbol', $map[$state]);
                                }
                            }),
                        Forms\Components\TextInput::make('currency_symbol')
                            ->label('Currency Symbol / Prefix')
                            ->placeholder('e.g. $, €, PKR ')
                            ->helperText('This is shown before amounts on invoices, quotes, and salaries.')
                            ->maxLength(10),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Invoice Terms & Conditions')
                    ->description('Displayed at the bottom of all invoices and in the invoice PDF.')
                    ->schema([
                        Forms\Components\Textarea::make('terms_conditions')
                            ->label('Invoice Terms & Conditions')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Lease Agreement Terms & Conditions')
                    ->description('Default terms for new lease agreements. Will be overridden if custom terms are set on a specific lease.')
                    ->schema([
                        Forms\Components\RichEditor::make('agreement_terms_conditions')
                            ->label('Agreement Terms & Conditions')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Quote Terms & Conditions')
                    ->description('Pre-filled on all new quotes. Can be edited per-quote.')
                    ->schema([
                        Forms\Components\Textarea::make('quote_terms_conditions')
                            ->label('Quote Terms & Conditions')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Payment Reminders')
                    ->description('Configure automated payment reminders.')
                    ->schema([
                        Forms\Components\TextInput::make('reminder_days_before')
                            ->label('Days Before Due Date')
                            ->placeholder('e.g. 7,3,1')
                            ->helperText('Comma-separated list of days before due date to send reminders.'),
                        Forms\Components\TextInput::make('reminder_whatsapp_template')
                            ->label('WhatsApp Template Name')
                            ->placeholder('e.g. payment_reminder')
                            ->default('payment_reminder'),
                        Forms\Components\TextInput::make('reminder_whatsapp_language')
                            ->label('WhatsApp Template Language')
                            ->placeholder('e.g. en_US')
                            ->default('en_US'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Admin Notifications')
                    ->description('Configure admin notifications for incoming messages.')
                    ->schema([
                        Forms\Components\TextInput::make('admin_notification_email')
                            ->label('Admin Notification Email')
                            ->email()
                            ->helperText('Email address to receive notifications for new WhatsApp messages.'),
                        Forms\Components\TextInput::make('admin_whatsapp_phone')
                            ->label('Admin WhatsApp Phone')
                            ->tel()
                            ->helperText('Phone number to include in reminders for queries.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
