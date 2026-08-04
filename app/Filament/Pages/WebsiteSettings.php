<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                        Textarea::make('maintenance_message')->rows(2)->helperText('Shown when the site is in maintenance mode.'),
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
