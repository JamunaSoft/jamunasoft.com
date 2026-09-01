<?php

namespace App\Filament\Pages;

use App\Services\Registrars\RegistrarManager;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WebsiteSettings extends Page
{
    protected string $view = 'filament.pages.settings-form';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 35;

    protected static ?string $title = 'Website Settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('settings.manage');
    }

    public function mount(): void
    {
        $this->form->fill(Settings::all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')->tabs([
                    Tab::make('General')->schema([
                        TextInput::make('company_name')->label('Company name'),
                        TextInput::make('legal_name'),
                        TextInput::make('site_title')->label('Website title'),
                        TextInput::make('tagline'),
                        TextInput::make('tagline_bn')->label('Tagline (Bengali)'),
                        FileUpload::make('logo_path')->label('Logo')->image()->disk('public')->directory('branding')->maxSize(1024),
                        FileUpload::make('logo_dark_path')->label('Dark logo')->image()->disk('public')->directory('branding')->maxSize(1024),
                        FileUpload::make('favicon_path')->label('Favicon')->disk('public')->directory('branding')->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml'])->maxSize(512),
                        FileUpload::make('og_image_path')->label('Default social sharing image')->image()->disk('public')->directory('branding')->maxSize(2048),
                    ]),

                    Tab::make('Contact')->schema([
                        TextInput::make('phone_primary')->label('Primary phone'),
                        TextInput::make('phone_secondary')->label('Secondary phone'),
                        TextInput::make('whatsapp_number')->label('WhatsApp number')->helperText('International format, e.g. 8801XXXXXXXXX'),
                        TextInput::make('email_primary')->email()->label('Primary email'),
                        TextInput::make('email_support')->email()->label('Support email'),
                        Textarea::make('office_address')->rows(2),
                        TextInput::make('google_map_embed')->label('Google Map embed URL')->helperText('The https://www.google.com/maps/embed?... URL only'),
                        TextInput::make('business_hours')->placeholder('Sat–Thu, 10:00 AM – 7:00 PM'),
                    ]),

                    Tab::make('Header & Footer')->schema([
                        TextInput::make('header_cta_label')->label('Header CTA label')->placeholder('Get a Quotation'),
                        TextInput::make('header_cta_url')->label('Header CTA URL')->placeholder('/request-a-quotation'),
                        TextInput::make('client_portal_url')->label('Client portal URL')->helperText('Leave blank to hide the Client Portal link.'),
                        Textarea::make('footer_text')->rows(2),
                        TextInput::make('copyright_text'),
                    ]),

                    Tab::make('SEO & Scripts')->schema([
                        TextInput::make('seo_default_title')->label('Default SEO title'),
                        Textarea::make('seo_default_description')->label('Default SEO description')->rows(2)->maxLength(170),
                        TextInput::make('google_analytics_id')->label('Google Analytics ID')->placeholder('G-XXXXXXX'),
                        TextInput::make('gtm_id')->label('Google Tag Manager ID')->placeholder('GTM-XXXXXXX'),
                        TextInput::make('meta_pixel_id')->label('Meta Pixel ID'),
                        TextInput::make('search_console_verification')->label('Search Console verification code'),
                        Textarea::make('custom_head_scripts')->rows(3)->helperText('Raw HTML injected into <head>. Super Admin responsibility — scripts run on every page.'),
                        Textarea::make('custom_body_scripts')->rows(3)->helperText('Raw HTML injected before </body>.'),
                    ]),

                    Tab::make('Forms & Notifications')->schema([
                        TextInput::make('contact_form_recipients')->helperText('Comma-separated email addresses notified of contact messages.'),
                        TextInput::make('lead_notification_recipients')->helperText('Comma-separated email addresses notified of new leads.'),
                        TextInput::make('ticket_notification_recipients')->helperText('Comma-separated email addresses notified of support tickets. Falls back to contact form recipients.'),
                        Textarea::make('maintenance_message')->rows(2)->helperText('Shown when the site is in maintenance mode.'),
                    ]),

                    Tab::make('Domains')->schema([
                        Toggle::make('domain_auto_invoice')
                            ->label('Auto-invoice domain renewals')
                            ->helperText('WHMCS-style: 30 days before a customer domain expires, a renewal order + invoice is created and emailed automatically. Off = reminder emails only.'),
                        Select::make('active_domain_registrar')
                            ->label('Purchase registrar')
                            ->options(RegistrarManager::PROVIDERS)
                            ->default('spaceship')
                            ->helperText('New registrations and the public availability search go through this provider. Renewals always use the registrar that already holds the domain. ResellCube needs RESELLCUBE_USER_ID and RESELLCUBE_API_KEY in .env.'),
                        Textarea::make('domain_default_nameservers')
                            ->label('Default nameservers')
                            ->rows(3)
                            ->placeholder("cl1.jamunasoft.com\ncl2.jamunasoft.com")
                            ->helperText('One per line. New registrations are automatically pointed here. Leave empty to use cl1/cl2.jamunasoft.com.'),
                        Textarea::make('domain_payment_instructions')
                            ->label('Payment instructions')
                            ->rows(3)
                            ->helperText('Shown to customers after placing a domain order and in the confirmation email, e.g. bKash number and steps.'),
                        TextInput::make('domain_order_recipients')
                            ->label('Order notification recipients')
                            ->helperText('Comma-separated emails notified of new domain orders. Falls back to lead recipients.'),
                        TextInput::make('domain_registrant_first_name')
                            ->label('Registrant first name')
                            ->helperText('The default WHOIS contact used for all registrations (the white-label proxy contact). All Spaceship/ICANN emails go to this contact, never to customers.'),
                        TextInput::make('domain_registrant_last_name')->label('Registrant last name'),
                        TextInput::make('domain_registrant_org')->label('Registrant organization'),
                        TextInput::make('domain_registrant_email')->email()->label('Registrant email')
                            ->helperText('Must be an inbox your team monitors — ICANN verification emails arrive here and must be acted on within 15 days.'),
                        TextInput::make('domain_registrant_phone')->label('Registrant phone')
                            ->placeholder('+880.1XXXXXXXXX')
                            ->helperText('EPP format: +<country code>.<number>'),
                        TextInput::make('domain_registrant_address')->label('Registrant address'),
                        TextInput::make('domain_registrant_city')->label('Registrant city'),
                        TextInput::make('domain_registrant_postal')->label('Registrant postal code'),
                        TextInput::make('domain_registrant_country')->label('Registrant country (ISO code)')->placeholder('BD')->maxLength(2),
                    ]),

                    Tab::make('Billing')->schema([
                        TextInput::make('invoice_ahead_days')
                            ->label('Invoice lead time (days)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('7')
                            ->helperText('Recurring service invoices are generated and emailed this many days before the due date. E.g. 14 = a bill due 15 Sep goes out on 1 Sep.'),
                        TextInput::make('invoice_generation_day')
                            ->label('Monthly billing day (optional)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(28)
                            ->placeholder('e.g. 1')
                            ->helperText('Set a day of the month (e.g. 1) to send ALL renewals due in the following 31 days on that one day — every client gets billed the same date regardless of their due date. Leave empty to bill purely by lead time. The lead time still acts as a safety net for services added mid-month.'),
                        TextInput::make('invoice_tagline')
                            ->label('Invoice tagline')
                            ->placeholder('Your Gateway to Innovation, Powered by AARISH ENTERPRISE')
                            ->helperText('Shown under the logo on invoice PDFs.'),
                        Repeater::make('invoice_banks')
                            ->label('Bank accounts')
                            ->helperText('Shown side by side in the payment section of invoice PDFs.')
                            ->schema([
                                TextInput::make('account_name')->label('Account name')->placeholder('AARISH ENTERPRISE'),
                                TextInput::make('account_number')->label('Account number'),
                                TextInput::make('bank_name')->label('Bank')->placeholder('Dutch-Bangla Bank Ltd'),
                                TextInput::make('branch')->placeholder('Shewrapara Branch'),
                                TextInput::make('routing_number')->label('Routing number'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->maxItems(3)
                            ->addActionLabel('Add bank account'),
                        TextInput::make('invoice_bkash')->label('bKash number'),
                        TextInput::make('invoice_nagad')->label('Nagad number'),
                        TextInput::make('invoice_rocket')->label('Rocket number'),
                        TextInput::make('invoice_price_note')
                            ->label('Price note')
                            ->placeholder('* All prices are excluding of VAT & AIT')
                            ->helperText('Small print under the invoice totals. Leave blank to use the default.'),
                        Textarea::make('invoice_note')
                            ->label('Invoice note (NB)')
                            ->rows(2)
                            ->helperText('Highlighted note on invoice PDFs, e.g. the hosting-expiry warning. Leave blank to use the default.'),
                    ]),
                ])->persistTab(),
            ]);
    }

    public function save(): void
    {
        Settings::set($this->form->getState(), 'website');

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }
}
