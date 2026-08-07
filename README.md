# Kirby Auto Nbsp

Kirby Auto Nbsp replaces ordinary spaces with visible `&nbsp;` entities in places that should not wrap, such as short prepositions, titles, dates, units, initials, and numeric ranges. It combines global replacements with the current Kirby language, lazily loading only the required files from `replacements/`; Czech, English, German, and Slovak are included, while unsupported languages use only global rules.

The plugin is intended for user-entered text and simple HTML. It avoids replacing text inside HTML tags, but it is not a full HTML parser for large code blocks or complex markup.

## PHP helper

Use `nbsp()` with a string and, optionally, a language code. Without one, it uses Kirby's current language and falls back to Kirby's global `language` option, or `en` when that option is not set.

```php
echo nbsp('19:00 - 22:00');
// 19:00&nbsp;-&nbsp;22:00

echo nbsp('8. August', 'de');
// 8.&nbsp;August
```

The helper always returns a string.

## Barista filter

When [Kirby Barista](https://github.com/jan-herman/kirby-barista) initializes Latte, the plugin registers an `nbsp` filter that accepts text or captured HTML and returns HTML-safe output containing literal `&nbsp;` entities.

```latte
{$text|nbsp}
{$text|nbsp:'de'}

{block time|nbsp}
	19:00 - 22:00
{/block}
```

## Options

Options can be set in `config.php`; all rule options default to `true`. The top-level `language` setting is Kirby's global option, not an Auto Nbsp option.

```php
return [
    'language' => 'en',
    'jan-herman.auto-nbsp' => [
        'debug' => false,
        'rules' => [
            'units' => true,
            'ratios' => true,
        ],
    ],
];
```

Option names below are relative to `jan-herman.auto-nbsp`.

| Option | Default | Description |
| --- | --- | --- |
| `debug` | `false` | Wraps every inserted non-breaking space in a red-highlighted `<span>` for visual debugging. |
| `customReplacements` | `[]` | Extends built-in replacement groups for each supplied language code and removes duplicate values. |
| `rules.prepositionsConjunctions` | `true` | Keeps configured short prepositions and conjunctions with the following word; `in Berlin` becomes `in&nbsp;Berlin`. |
| `rules.articles` | `true` | Keeps configured articles with the following word; `the venue` becomes `the&nbsp;venue`. |
| `rules.abbreviations` | `true` | Keeps configured abbreviations with the following word or number and joins their internal spaces; `z. B. Berlin` becomes `z.&nbsp;B.&nbsp;Berlin`. |
| `rules.titles` | `true` | Keeps configured titles and credentials with the associated name; `Dr. Smith` becomes `Dr.&nbsp;Smith`. |
| `rules.units` | `true` | Keeps numbers with configured units, symbols, and trailing currency codes; `20 °C` becomes `20&nbsp;°C`. |
| `rules.months` | `true` | Keeps a number and optional ordinal dot with a configured month name; `8. August` becomes `8.&nbsp;August`. |
| `rules.afterNumbers` | `true` | Keeps a number with a following word when they are separated by whitespace; `20 people` becomes `20&nbsp;people`. |
| `rules.betweenNumbers` | `true` | Keeps grouped numbers, numeric dates, and spaced numeric ranges together; `8. 8. 2025` becomes `8.&nbsp;8.&nbsp;2025`. |
| `rules.nameInitials` | `true` | Keeps one or more uppercase name initials with the following surname; `J. A. Komenský` becomes `J.&nbsp;A.&nbsp;Komenský`. |
| `rules.symbolsBeforeNumbers` | `true` | Keeps configured leading symbols with the number that follows; `§ 9` becomes `§&nbsp;9`. |
| `rules.ordinals` | `true` | Keeps language-aware dotted ordinal numbers with the following word; `5. Kapitel` becomes `5.&nbsp;Kapitel`. |
| `rules.compoundAbbreviations` | `true` | Joins internal spaces in groups of two or more single-letter abbreviations; `s. r. o.` becomes `s.&nbsp;r.&nbsp;o.` |
| `rules.ratios` | `true` | Keeps whitespace-separated numeric ratios and scales together without affecting times; `1 : 50 000` becomes `1&nbsp;:&nbsp;50&nbsp;000`. |

### Custom replacements

`customReplacements` extends the built-in replacements without removing existing values. Its first-level keys are language codes or `*` for global replacements, and its second-level keys are replacement groups such as `articles`, `prepositionsConjunctions`, `abbreviations`, `titlesBeforeName`, `titlesAfterName`, `units`, `months`, `symbolsBeforeNumbers`, `ordinalSuffixes`, or `ordinalWordCases`.

```php
return [
    'jan-herman.auto-nbsp' => [
        'customReplacements' => [
            '*' => [
                'units' => ['px'],
            ],
            'en' => [
                'prepositionsConjunctions' => ['via'],
                'abbreviations' => ['approx.'],
            ],
        ],
    ],
];
```

This adds `px` for every language and the two English replacements while retaining all defaults; exact duplicate values are removed automatically. See the built-in files in [`replacements/`](replacements/) for complete examples of the available groups and their structure.

## Direct usage

When using the formatter class directly, pass rule overrides as a partial camelCase array. Unspecified rules retain their defaults from `AutoNbsp::DEFAULT_RULES`.

```php
use JanHerman\AutoNbsp\AutoNbsp;

$formatter = new AutoNbsp(
    language: 'de',
    rules: [
        'units' => false,
    ],
);

echo $formatter->replace('8. August');
```
