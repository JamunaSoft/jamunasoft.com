<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class HomepageContent extends Page
{
    protected string $view = 'filament.pages.settings-form';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 36;

    protected static ?string $title = 'Homepage & About Content';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('settings.manage') || $user?->can('pages.manage'));
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
                Tabs::make('Content')->tabs([
                    Tab::make('Hero')->schema([
                        TextInput::make('hero_heading'),
                        TextInput::make('hero_heading_bn')->label('Hero heading (Bengali)'),
                        Textarea::make('hero_subheading')->rows(2),
                        Textarea::make('hero_subheading_bn')->label('Hero subheading (Bengali)')->rows(2),
                        TextInput::make('hero_primary_cta_label')->placeholder('Explore Our Services'),
                        TextInput::make('hero_primary_cta_url')->placeholder('/services'),
                        TextInput::make('hero_secondary_cta_label')->placeholder('Get Free Consultation'),
                        TextInput::make('hero_secondary_cta_url')->placeholder('/contact'),
                        FileUpload::make('hero_image_path')->label('Hero image / dashboard mockup')->image()->disk('public')->directory('homepage')->maxSize(3072),
                        Repeater::make('hero_badges')
                            ->simple(TextInput::make('badge')->required())
                            ->label('Hero badges')
                            ->defaultItems(0),
                    ]),

                    Tab::make('Statistics')->schema([
                        Repeater::make('stats')->schema([
                            TextInput::make('value')->required()->placeholder('120+'),
                            TextInput::make('label')->required()->placeholder('Completed Projects'),
                        ])->defaultItems(0)
                            ->helperText('Shown in the trust bar. Keep numbers honest.'),
                    ]),

                    Tab::make('Why Choose Us')->schema([
                        Repeater::make('why_us')->schema([
                            TextInput::make('title')->required(),
                            Textarea::make('description')->rows(2),
                        ])->defaultItems(0),
                    ]),

                    Tab::make('Work Process')->schema([
                        Repeater::make('process_steps')->schema([
                            TextInput::make('title')->required(),
                            Textarea::make('description')->rows(2),
                        ])->defaultItems(0),
                    ]),

                    Tab::make('Final CTA')->schema([
                        TextInput::make('cta_heading'),
                        TextInput::make('cta_heading_bn')->label('CTA heading (Bengali)'),
                        Textarea::make('cta_description')->rows(2),
                    ]),

                    Tab::make('Brand Story')->schema([
                        TextInput::make('brand_meaning')
                            ->label('Brand meaning (one line)')
                            ->helperText('Shown as the highlighted line of the brand story on the About page.'),
                        TextInput::make('brand_meaning_bn')->label('Brand meaning (Bengali)'),
                        Textarea::make('brand_story_intro')->label('Brand story introduction')->rows(2),
                        Textarea::make('brand_story_intro_bn')->label('Brand story introduction (Bengali)')->rows(2),
                        Repeater::make('brand_story_points')
                            ->label('Logo / brand meaning points')
                            ->schema([
                                TextInput::make('title')->required(),
                                Textarea::make('description')->rows(2),
                            ])->defaultItems(0),
                        Repeater::make('brand_story_points_bn')
                            ->label('Logo / brand meaning points (Bengali)')
                            ->schema([
                                TextInput::make('title')->required(),
                                Textarea::make('description')->rows(2),
                            ])->defaultItems(0),
                    ]),

                    Tab::make('About Page')->schema([
                        Textarea::make('about_intro')->rows(3)->label('Company introduction'),
                        Textarea::make('about_story')->rows(4)->label('Company story'),
                        Textarea::make('mission')->rows(2),
                        Textarea::make('vision')->rows(2),
                        Repeater::make('core_values')->schema([
                            TextInput::make('title')->required(),
                            Textarea::make('description')->rows(2),
                        ])->defaultItems(0),
                    ]),
                ])->persistTab(),
            ]);
    }

    public function save(): void
    {
        Settings::set($this->form->getState(), 'content');

        Notification::make()
            ->success()
            ->title('Content saved')
            ->send();
    }
}
