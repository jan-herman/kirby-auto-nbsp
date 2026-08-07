<?php

use Kirby\Cms\App as Kirby;
use Kirby\Content\Field;
use JanHerman\AutoNbsp\AutoNbsp;
use Latte\ContentType;
use Latte\Runtime\FilterInfo;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('jan-herman/auto-nbsp', [
    'options' => [
        'debug' => false,
        'customReplacements' => [], // 'language code or *' => 'prepositionsConjunctions', 'articles', 'abbreviations', 'units', 'months', 'titlesBeforeName', 'titlesAfterName', 'symbolsBeforeNumbers', 'ordinalSuffixes', 'ordinalWordCases'
        'rules' => AutoNbsp::DEFAULT_RULES,
    ],
    // field method
    'fieldMethods' => [
        'nbsp' => function (Field $field, ?string $language = null) {
            $field->value = nbsp($field->value, $language);
            return $field;
        }
    ],
    // latte filter
    'hooks' => [
        'jan-herman.barista.init:after' => function ($latte) {
            $latte->addFilter('nbsp', function (FilterInfo $info, string $string, ?string $language = null) {
                $info->validate([null, ContentType::Text, ContentType::Html], 'nbsp');
                $formattedString = nbsp($string, $language);

                if ($info->contentType !== ContentType::Html) {
                    $formattedString = (string) safe_html($formattedString);
                }

                $info->contentType = ContentType::Html;
                return $formattedString;
            });
        }
    ],
]);

// Helper function
if (!function_exists('nbsp')) {
    function nbsp(string $string, ?string $language = null): string
    {
        $language ??= kirby()->languageCode() ?? option('language', 'en');

        static $autoNbsp = null;

        $autoNbsp ??= new AutoNbsp(
            language: option('language', 'en'),
            customReplacements: option('jan-herman.auto-nbsp.customReplacements', []),
            rules: option('jan-herman.auto-nbsp.rules', []),
            debug: option('jan-herman.auto-nbsp.debug', false)
        );

        return $autoNbsp->replace($string, $language);
    }
}
