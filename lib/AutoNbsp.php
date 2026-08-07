<?php

namespace JanHerman\AutoNbsp;

/**
 * Class AutoNbsp
 *
 * This class replaces spaces with non-breaking spaces (&nbsp;) in specified locations within a string.
 *
 * The primary purpose is to prevent short words (like prepositions, conjunctions, and articles) from
 * appearing at the end of a line, adhering to typographical rules for a given language. This enhances
 * the readability and aesthetic quality of text.
 */
class AutoNbsp
{
    public const DEFAULT_RULES = [
        'prepositionsConjunctions' => true,
        'articles' => true,
        'abbreviations' => true,
        'titles' => true,
        'units' => true,
        'months' => true,
        'afterNumbers' => true,
        'betweenNumbers' => true,
        'nameInitials' => true,
        'symbolsBeforeNumbers' => true,
        'ordinals' => true,
        'compoundAbbreviations' => true,
        'ratios' => true,
    ];

    /**
     * Array of replacements for non-breaking spaces
     *
     * @var array
     */
    private array $replacements = [];

    /**
     * Generated regex alternatives keyed by their source words
     *
     * @var array
     */
    private array $regexCache = [];

    /**
     * Enabled replacement rules
     *
     * @var array
     */
    private array $rules;

    /**
     * Non-breaking space character (or HTML entity)
     *
     * @var string
     */
    private string $nbsp;

    /**
     * AutoNbsp constructor.
     *
     * @param string $language Language code (e.g., 'en', 'cs')
     * @param array $customReplacements Custom replacements to merge with the default ones
     * @param array $rules Replacement rules to override
     * @param bool $debug Flag to enable debug mode
     */
    public function __construct(
        private string $language = 'en',
        private array $customReplacements = [],
        array $rules = [],
        private bool $debug = false
    ) {
        $this->rules = array_replace(self::DEFAULT_RULES, $rules);
        $this->nbsp = $this->debug ? '<span style="background:red;">&nbsp;</span>' : '&nbsp;';
    }

    /**
     * Convert an array of words to a regex pattern for matching
     *
     * @param array $words Array of words to include in the regex
     * @return string Regex pattern
     */
    private function arrayToRegex(array $words): string
    {
        $cacheKey = serialize($words);

        if (isset($this->regexCache[$cacheKey])) {
            return $this->regexCache[$cacheKey];
        }

        usort($words, fn ($a, $b) => strlen($b) <=> strlen($a));

        return $this->regexCache[$cacheKey] = implode('|', array_map(fn ($str) => preg_quote($str, '/'), $words));
    }

    /**
     * Load and cache replacements for a language.
     *
     * @param string $language Language code or * for global replacements
     * @return array The replacements for the requested language
     */
    private function loadReplacements(string $language): array
    {
        if (array_key_exists($language, $this->replacements)) {
            return $this->replacements[$language];
        }

        $filename = $language === '*' ? 'global' : $language;
        $defaults = [];

        if (preg_match('/^[a-z0-9_-]+$/iD', $filename)) {
            $file = dirname(__DIR__) . '/replacements/' . $filename . '.php';

            if (is_file($file)) {
                $defaults = require $file;

                if (!is_array($defaults)) {
                    throw new \UnexpectedValueException("Replacement file {$file} must return an array");
                }
            }
        }

        $customReplacements = $this->customReplacements[$language] ?? [];

        if (!is_array($customReplacements)) {
            throw new \UnexpectedValueException("Custom replacements for {$language} must be an array");
        }

        foreach ($customReplacements as $key => $values) {
            if (!is_array($values)) {
                throw new \UnexpectedValueException("Custom replacement group {$key} for {$language} must be an array");
            }

            $defaults[$key] = array_values(array_unique(array_merge($defaults[$key] ?? [], $values)));
        }

        return $this->replacements[$language] = $defaults;
    }

    /**
     * Load all available replacement files and custom replacements.
     *
     * @return array All replacements keyed by language
     */
    private function loadAllReplacements(): array
    {
        $languages = ['*'];

        foreach (glob(dirname(__DIR__) . '/replacements/*.php') ?: [] as $file) {
            $language = pathinfo($file, PATHINFO_FILENAME);
            $languages[] = $language === 'global' ? '*' : $language;
        }

        $languages = array_unique(array_merge($languages, array_keys($this->customReplacements)));
        $replacements = [];

        foreach ($languages as $language) {
            $replacements[$language] = $this->loadReplacements($language);
        }

        return $replacements;
    }

    /**
     * Merge replacement maps without replacing complete groups.
     *
     * @param array ...$replacementSets Replacement maps to merge
     * @return array The merged replacement map
     */
    private function mergeReplacementGroups(array ...$replacementSets): array
    {
        $merged = [];

        foreach ($replacementSets as $replacementSet) {
            foreach ($replacementSet as $key => $values) {
                $merged[$key] = array_merge($merged[$key] ?? [], $values);
            }
        }

        return $merged;
    }

    /**
     * Get the replacement patterns for a specific key and language.
     *
     * @param string|null $key The specific key of replacements (e.g., 'articles')
     * @param string|null $language The language code (e.g., 'en')
     * @return array The replacements for the specified key and language
     */
    public function getReplacements(?string $key = null, ?string $language = null): array
    {
        if ($key === null && $language === null) {
            return $this->loadAllReplacements();
        }

        if ($key === null) {
            $global = $this->loadReplacements('*');
            $languageSpecific = $this->loadReplacements($language);

            return $this->mergeReplacementGroups($global, $languageSpecific);
        }

        $global = $this->loadReplacements('*')[$key] ?? [];
        $languageSpecific = $this->loadReplacements($language ?? $this->language)[$key] ?? [];

        return array_merge($global, $languageSpecific);
    }

    /**
     * Replace spaces after specified words with non-breaking spaces.
     *
     * @param string $string The input string
     * @param array $words Array of words after which spaces should be replaced
     * @return string The processed string
     */
    public function afterWords(string $string, array $words): string
    {
        $pattern = '/(?<!\w)(' . $this->arrayToRegex($words) . ')\s+(?=[^>]*?(<|$))/ui';
        return preg_replace_callback($pattern, function ($matches) {
            $word = preg_replace('/\s+/u', $this->nbsp, $matches[1]);
            return $word . $this->nbsp;
        }, $string);
    }

    /**
     * Replace spaces before specified words with non-breaking spaces.
     *
     * @param string $string The input string
     * @param array $words Array of words before which spaces should be replaced
     * @return string The processed string
     */
    public function beforeWords(string $string, array $words): string
    {
        $pattern = '/\s+(' . $this->arrayToRegex($words) . ')(?!\w)(?=[^>]*?(<|$))/ui';
        return preg_replace_callback($pattern, function ($matches) {
            $word = preg_replace('/\s+/u', $this->nbsp, $matches[1]);
            return $this->nbsp . $word;
        }, $string);
    }

    /**
     * Replace spaces after numbers with non-breaking spaces.
     *
     * @param string $string The input string
     * @return string The processed string
     */
    public function afterNumbers(string $string): string
    {
        $pattern = '/(\d)\s+(\b)(?=[^>]*?(<|$))/';
        return preg_replace($pattern, '$1' . $this->nbsp . '$2', $string);
    }

    /**
     * Replace spaces between numbers with non-breaking spaces.
     *
     * @param string $string The input string
     * @return string The processed string
     */
    public function betweenNumbers(string $string): string
    {
        $rangePattern = '/(?<=\d)(\.?)\s+([-–—])\s+(?=\d)(?=[^>]*?(<|$))/u';
        $string = preg_replace($rangePattern, '$1' . $this->nbsp . '$2' . $this->nbsp, $string);

        $pattern = '/(?<=\d)(\.?)\s+(\d)(?=[^>]*?(<|$))/';
        return preg_replace($pattern, '$1' . $this->nbsp . '$2', $string);
    }

    /**
     * Replace spaces between name initials and before the following surname.
     *
     * @param string $string The input string
     * @return string The processed string
     */
    public function nameInitials(string $string): string
    {
        $pattern = '/(?<![\p{L}\p{N}.])((?:\p{Lu}\.\s+)+)(?=\p{Lu}[\p{L}\p{M}\x{2019}\x{0027}-]+)(?=[^>]*?(<|$))/u';

        return preg_replace_callback($pattern, function ($matches) {
            return preg_replace('/\s+/u', $this->nbsp, $matches[1]);
        }, $string);
    }

    /**
     * Replace spaces between specified symbols and following numbers.
     *
     * @param string $string The input string
     * @param string|null $language Language code (e.g., 'en', 'cs')
     * @return string The processed string
     */
    public function symbolsBeforeNumbers(string $string, ?string $language = null): string
    {
        $symbols = $this->getReplacements('symbolsBeforeNumbers', $language);

        if (!$symbols) {
            return $string;
        }

        $pattern = '/(?<![\p{L}\p{N}])(' . $this->arrayToRegex($symbols) . ')\s+(?=\d)(?=[^>]*?(<|$))/u';
        return preg_replace($pattern, '$1' . $this->nbsp, $string);
    }

    /**
     * Replace spaces between ordinal numbers and following lowercase words.
     *
     * @param string $string The input string
     * @param string|null $language Language code (e.g., 'en', 'cs')
     * @return string The processed string
     */
    public function ordinals(string $string, ?string $language = null): string
    {
        $suffixes = $this->getReplacements('ordinalSuffixes', $language);

        if (!$suffixes) {
            return $string;
        }

        $wordCases = $this->getReplacements('ordinalWordCases', $language) ?: ['lowercase'];
        $casePatterns = [
            'lowercase' => '\p{Ll}',
            'uppercase' => '\p{Lu}',
        ];
        $wordPatterns = array_values(array_intersect_key($casePatterns, array_flip($wordCases)));

        if (!$wordPatterns) {
            return $string;
        }

        $pattern = '/(?<![\p{L}\p{N}.,])(\d+)(' . $this->arrayToRegex($suffixes) . ')\s+(?=' . implode('|', $wordPatterns) . ')(?=[^>]*?(<|$))/u';
        return preg_replace($pattern, '$1$2' . $this->nbsp, $string);
    }

    /**
     * Replace internal spaces in compound abbreviations and initial groups.
     *
     * @param string $string The input string
     * @return string The processed string
     */
    public function compoundAbbreviations(string $string): string
    {
        $pattern = '/(?<![\p{L}\p{N}.])((?:\p{L}\.\s+)+\p{L}\.)(?!\p{L})(?=[^>]*?(<|$))/u';

        return preg_replace_callback($pattern, function ($matches) {
            return preg_replace('/\s+/u', $this->nbsp, $matches[1]);
        }, $string);
    }

    /**
     * Replace spaces around colons in ratios and scales.
     *
     * @param string $string The input string
     * @return string The processed string
     */
    public function ratios(string $string): string
    {
        $pattern = '/(?<=\d)\s+:\s+(?=\d)(?=[^>]*?(<|$))/u';
        return preg_replace($pattern, $this->nbsp . ':' . $this->nbsp, $string);
    }

    /**
     * Replace spaces before months with non-breaking spaces.
     *
     * @param string $string The input string
     * @param string|null $language Language code (e.g., 'en', 'cs')
     * @return string The processed string
     */
    public function beforeMonths(string $string, ?string $language = null): string
    {
        $months = $this->getReplacements('months', $language);

        if (!$months) {
            return $string;
        }

        $pattern = '/(?<=\d)(\.?)\s+(' . $this->arrayToRegex($months) . ')(?!\w)(?=[^>]*?(<|$))/ui';
        return preg_replace($pattern, '$1' . $this->nbsp . '$2', $string);
    }

    /**
     * Replace spaces before units with non-breaking spaces.
     *
     * @param string $string The input string
     * @param string|null $language Language code (e.g., 'en', 'cs')
     * @return string The processed string
     */
    public function beforeUnits(string $string, ?string $language = null): string
    {
        $units = $this->getReplacements('units', $language);
        $pattern = '/(\d+)\s+(' . $this->arrayToRegex($units) . ')(?!\w)(?=[^>]*?(<|$))/u';
        return preg_replace($pattern, '$1' . $this->nbsp . '$2', $string);
    }

    /**
     * Replace specified spaces with non-breaking spaces in the given string based on configuration.
     *
     * @param string $string The input string
     * @param string|null $language Language code (e.g., 'en', 'cs')
     * @return string The processed string
     */
    public function replace(string $string, ?string $language = null): string
    {
        if (str_contains($string, ' ') === false && preg_match('/\s/u', $string) === 0) {
            return $string;
        }

        // spaces before words
        if ($this->rules['titles']) {
            $beforeWords = $this->getReplacements('titlesAfterName', $language);
            $string = $this->beforeWords($string, $beforeWords);
        }

        // spaces after words
        $afterWords = array_merge(
            $this->rules['prepositionsConjunctions'] ? $this->getReplacements('prepositionsConjunctions', $language) : [],
            $this->rules['articles'] ? $this->getReplacements('articles', $language) : [],
            $this->rules['titles'] ? $this->getReplacements('titlesBeforeName', $language) : [],
            $this->rules['abbreviations'] ? $this->getReplacements('abbreviations', $language) : []
        );
        if ($afterWords) {
            $string = $this->afterWords($string, $afterWords);
        }

        // spaces between name initials and before a surname
        if ($this->rules['nameInitials']) {
            $string = $this->nameInitials($string);
        }

        // internal spaces in compound abbreviations and initial groups
        if ($this->rules['compoundAbbreviations']) {
            $string = $this->compoundAbbreviations($string);
        }

        $hasNumbers = strpbrk($string, '0123456789') !== false;

        if ($hasNumbers === false) {
            $hasNumbers = preg_match('/\d/u', $string) !== 0;
        }

        if ($hasNumbers) {
            // spaces between number and a month
            if ($this->rules['months']) {
                $string = $this->beforeMonths($string, $language);
            }

            // spaces after a number
            if ($this->rules['afterNumbers']) {
                $string = $this->afterNumbers($string);
            }

            // spaces between two numbers
            if ($this->rules['betweenNumbers']) {
                $string = $this->betweenNumbers($string);
            }

            // spaces around colons in ratios and scales
            if ($this->rules['ratios']) {
                $string = $this->ratios($string);
            }

            // spaces between symbols and following numbers
            if ($this->rules['symbolsBeforeNumbers']) {
                $string = $this->symbolsBeforeNumbers($string, $language);
            }

            // spaces between ordinal numbers and following words
            if ($this->rules['ordinals']) {
                $string = $this->ordinals($string, $language);
            }

            // spaces between a number and a unit
            if ($this->rules['units']) {
                $string = $this->beforeUnits($string, $language);
            }
        }

        return $string;
    }
}
