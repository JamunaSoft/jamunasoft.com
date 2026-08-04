<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Reusable schema fragments shared across admin resources.
 */
class Schemas
{
    public static function seoSection(): Section
    {
        return Section::make('SEO')
            ->description('Search engine metadata. Leave blank to use sensible defaults.')
            ->collapsed()
            ->schema([
                TextInput::make('seo_title')
                    ->label('SEO title')
                    ->maxLength(70),
                Textarea::make('seo_description')
                    ->label('SEO description')
                    ->rows(2)
                    ->maxLength(170),
                Toggle::make('seo_noindex')
                    ->label('Hide from search engines (noindex)'),
            ]);
    }

    /**
     * Bengali override fields writing into the `translations` JSON column.
     *
     * @param  array<string, string>  $fields  field name => label; prefix name with "long:" for a textarea
     */
    public static function bengaliSection(array $fields): Section
    {
        $components = [];

        foreach ($fields as $name => $label) {
            if (str_starts_with($name, 'long:')) {
                $components[] = Textarea::make('translations.bn.'.substr($name, 5))
                    ->label($label)
                    ->rows(3);
            } else {
                $components[] = TextInput::make("translations.bn.{$name}")
                    ->label($label);
            }
        }

        return Section::make('Bengali translation (বাংলা)')
            ->description('Optional Bengali overrides shown when visitors switch to Bengali.')
            ->collapsed()
            ->schema($components);
    }
}
