<?php

use Kirby\Cms\App as Kirby;
use JanHerman\AutoNbsp\AutoNbsp;
use Latte\Runtime\Html;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('jan-herman/auto-nbsp', [
    'options' => [
        'debug' => true,
        'customReplacements' => [], // 'language_code or *' => 'prepositions_conjunctions', 'articles', 'abbreviations', 'units', 'months', 'titles_before_name', 'titles_after_name'
        'rules' => [
            'prepositionsConjunctions' => true,
            'articles' => true,
            'abbreviations' => true,
            'titles' => true,
            'units' => false,
            'months' => true,
            'afterNumbers' => true,
            'betweenNumbers' => true,
        ]
    ],
    // field method
    'fieldMethods' => [
        'nbsp' => function ($field) {
            $field->value = nbsp($field->value);
            return $field;
        }
    ],
    // latte filter
    'hooks' => [
        'jan-herman.barista.init:after' => function ($latte) {
            $latte->addFilter('nbsp', function (string $string) {
                $formated_string = nbsp($string);
                return new Html($formated_string);
            });
        }
    ],
]);

function nbsp(string $string): string
{
    $kirby = kirby();

    $auto_nbsp = AutoNbsp::getInstance(
        language: $kirby->language()->code(),
        custom_replacements: option('jan-herman.auto-nbsp.customReplacements'),
        prepositions_conjunctions: option('jan-herman.auto-nbsp.rules.prepositionsConjunctions'),
        articles: option('jan-herman.auto-nbsp.rules.articles'),
        abbreviations: option('jan-herman.auto-nbsp.rules.abbreviations'),
        titles: option('jan-herman.auto-nbsp.rules.titles'),
        units: option('jan-herman.auto-nbsp.rules.units'),
        months: option('jan-herman.auto-nbsp.rules.months'),
        after_numbers: option('jan-herman.auto-nbsp.rules.afterNumbers'),
        between_numbers: option('jan-herman.auto-nbsp.rules.betweenNumbers'),
        debug: option('jan-herman.auto-nbsp.debug')
    );

    return $auto_nbsp->replace($string);
}
