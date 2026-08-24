<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaseResource\Pages;
use App\Models\Lease;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaseResource extends Resource
{
    protected static ?string $model = Lease::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Tenants';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Lease Details')
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->relationship('tenant', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => \App\Models\Tenant::query()
                            ->where(fn ($q) => $q
                                ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                            )
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($t) => [$t->id => "{$t->first_name} {$t->last_name}"])
                        )
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\Section::make('Personal Details')
                                ->schema([
                                    Forms\Components\TextInput::make('first_name')->required(),
                                    Forms\Components\TextInput::make('last_name')->required(),
                                    Forms\Components\TextInput::make('emirates_id')
                                        ->label('Emirates ID')
                                        ->mask('999-9999-9999999-9'),
                                    Forms\Components\TextInput::make('company_name')->label('Company Name'),
                                ])->columns(2),
                            Forms\Components\Section::make('Contact Info')
                                ->schema([
                                    Forms\Components\TextInput::make('email')->email(),
                                    Forms\Components\TextInput::make('phone')->tel(),
                                    Forms\Components\TextInput::make('whatsapp_number')
                                        ->label('WhatsApp Number')
                                        ->tel()
                                        ->helperText('Include country code, e.g. +1234567890'),
                                ])->columns(2),
                            Forms\Components\Section::make('Alternative Contact')
                                ->schema([
                                    Forms\Components\TextInput::make('alt_name')->label('Alternative Name'),
                                    Forms\Components\TextInput::make('alt_phone')->label('Alternative Phone')->tel(),
                                ])->columns(2),
                            Forms\Components\Section::make('Emergency Contact')
                                ->schema([
                                    Forms\Components\TextInput::make('emergency_contact_name')->label('Emergency Contact Name'),
                                    Forms\Components\TextInput::make('emergency_contact_phone')->label('Emergency Contact Phone')->tel(),
                                ])->columns(2)->collapsible(),
                        ]),
                    Forms\Components\Select::make('unit_id')
                        ->relationship('unit', 'unit_number', fn ($query) => $query->where('status', 'available'))
                        ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->unit_number} — {$record->size} (" . \App\Models\Setting::money($record->monthly_price) . "/mo)")
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            if ($state) {
                                $unit = \App\Models\Unit::find($state);
                                if ($unit) {
                                    $set('monthly_rate', $unit->monthly_price);
                                }
                            }
                        }),
                    Forms\Components\DatePicker::make('move_in_date')
                        ->required()
                        ->default(now())
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('billing_day', $state ? Carbon::parse($state)->day : null)),
                    Forms\Components\DatePicker::make('move_out_date'),
                    Forms\Components\TextInput::make('monthly_rate')
                        ->required()
                        ->numeric()
                        ->prefix(fn () => \App\Models\Setting::currency())
                        ->helperText('Pre-filled from unit price. Override if needed.'),
                    Forms\Components\TextInput::make('billing_day')
                        ->required()
                        ->numeric()
                        ->default(now()->day)
                        ->minValue(1)
                        ->maxValue(31)
                        ->helperText('Auto-set from move-in date.'),
                    Forms\Components\TextInput::make('billing_interval_months')
                        ->label('Billing Interval (Months)')
                        ->required()
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->helperText('e.g., 1 for monthly, 3 for quarterly, 6 for semi-annually.'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'terminated' => 'Terminated',
                            'expired' => 'Expired',
                        ])
                        ->default('active')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Storage Category & Specifics')
                ->schema([
                    Forms\Components\Select::make('storage_type')
                        ->label('Storage Type')
                        ->options([
                            'business' => 'Business',
                            'personal' => 'Personal',
                        ])
                        ->placeholder('Select storage type')
                        ->native(false),
                    Forms\Components\Select::make('goods_condition')
                        ->label('Condition of Goods')
                        ->options([
                            'new' => 'New',
                            'used' => 'Used',
                        ])
                        ->placeholder('Select goods condition')
                        ->native(false),
                ])
                ->columns(2)
                ->collapsible(),

            Forms\Components\Section::make('Notes & Custom Terms')
                ->schema([
                    Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                    Forms\Components\TextInput::make('space_details')
                        ->label('Space Details')
                        ->placeholder('e.g. Unit 12B – Ground Floor, Near Entrance')
                        ->helperText('Optional. If set, this replaces the unit number on invoices and receipts.')
                        ->maxLength(255)->columnSpanFull(),
                    Forms\Components\RichEditor::make('custom_terms')
                        ->label('Custom Agreement Terms')
                        ->helperText('Leave blank to use the default terms configured in Settings. Any text entered here will completely override the default conditions on this specific Lease Agreement PDF.')
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Signed Agreement')
                ->description('Upload the signed copy of the lease agreement once the tenant has signed it.')
                ->schema([
                    Forms\Components\FileUpload::make('signed_agreement_path')
                        ->label('Signed Agreement (PDF)')
                        ->disk('public')
                        ->directory('signed-agreements')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->downloadable()
                        ->openable(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.full_name')
                    ->label('Tenant')
                    ->searchable(['tenant.first_name', 'tenant.last_name'])
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('unit.unit_number')
                    ->label('Unit')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('move_in_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('move_out_date')
                    ->date()
                    ->placeholder('Ongoing'),
                Tables\Columns\TextColumn::make('monthly_rate')
                    ->formatStateUsing(fn ($state) => \App\Models\Setting::money($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_day')
                    ->label('Bill Day')
                    ->formatStateUsing(fn ($state) => ordinal($state)),
                Tables\Columns\TextColumn::make('billing_interval_months')
                    ->label('Interval')
                    ->formatStateUsing(fn ($state) => $state == 1 ? 'Monthly' : "Every {$state} Mos"),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'terminated' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('signed_agreement_path')
                    ->label('Signed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (Lease $record) => $record->signed_agreement_path ? 'Signed agreement uploaded' : 'Awaiting signed agreement'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'terminated' => 'Terminated',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send_email')
                    ->label('Send Email')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->action(function (\App\Models\Lease $record) {
                        if ($record->tenant->email) {
                            $pdfPath = app(\App\Services\LeaseAgreementPdfService::class)->generateAndStore($record);
                            
                            $content = "A new lease agreement has been generated.\n\n" .
                                       "Lease ID: {$record->id}\n" .
                                       "Move In Date: {$record->move_in_date->format('F j, Y')}";

                            \Illuminate\Support\Facades\Mail::to($record->tenant->email)->send(
                                new \App\Mail\DocumentMail(
                                    "Lease Agreement {$record->id} — StorageCRM",
                                    $content,
                                    \Illuminate\Support\Facades\Storage::disk('local')->path($pdfPath),
                                    "lease-agreement-{$record->id}.pdf"
                                )
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Email sent successfully')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Tenant has no email address')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('agreement_pdf')
                    ->label('Agreement PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->url(fn (Lease $record) => route('leases.agreement.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('move_out_form')
                    ->label('Move Out Form')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->form([
                        Forms\Components\DatePicker::make('move_out_date')
                            ->label('Actual Move Out Date')
                            ->default(fn (Lease $record) => $record->move_out_date ?: now())
                            ->required(),
                    ])
                    ->action(function (Lease $record, array $data) {
                        $form = \App\Models\MoveOutForm::updateOrCreate(
                            ['lease_id' => $record->id],
                            ['move_out_date' => $data['move_out_date']]
                        );
                        return app(\App\Services\MoveOutFormPdfService::class)->download($form);
                    }),
                Tables\Actions\Action::make('terminate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Lease $record) => $record->status === 'active')
                    ->action(function (Lease $record) {
                        $record->update([
                            'status' => 'terminated',
                            'move_out_date' => now(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $leases = \App\Models\Lease::with(['tenant', 'unit.facility'])->get();
                        $csv = implode(',', ['ID','Tenant First Name','Tenant Last Name','Tenant Email','Tenant Phone','Unit Number','Facility','Move In Date','Move Out Date','Monthly Rate','Billing Interval (Months)','Status','Deposit','Created At']) . "\n";
                        foreach ($leases as $l) {
                            $csv .= implode(',', [
                                $l->id,
                                '"' . str_replace('"','""',$l->tenant?->first_name ?? '') . '"',
                                '"' . str_replace('"','""',$l->tenant?->last_name ?? '') . '"',
                                '"' . str_replace('"','""',$l->tenant?->email ?? '') . '"',
                                '"' . str_replace('"','""',$l->tenant?->phone ?? '') . '"',
                                '"' . str_replace('"','""',$l->unit?->unit_number ?? '') . '"',
                                '"' . str_replace('"','""',$l->unit?->facility?->name ?? '') . '"',
                                '"' . ($l->move_in_date?->format('Y-m-d') ?? '') . '"',
                                '"' . ($l->move_out_date?->format('Y-m-d') ?? '') . '"',
                                $l->monthly_rate ?? 0,
                                $l->billing_interval_months ?? 1,
                                '"' . str_replace('"','""',$l->status ?? '') . '"',
                                $l->deposit_amount ?? 0,
                                '"' . ($l->created_at?->format('Y-m-d H:i:s') ?? '') . '"',
                            ]) . "\n";
                        }
                        return response()->streamDownload(
                            fn () => print($csv),
                            'leases-' . now()->format('Y-m-d') . '.csv',
                            ['Content-Type' => 'text/csv']
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No leases yet')
            ->emptyStateDescription('Create a lease to link a tenant to a storage unit.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeases::route('/'),
            'create' => Pages\CreateLease::route('/create'),
            'edit' => Pages\EditLease::route('/{record}/edit'),
        ];
    }
}
