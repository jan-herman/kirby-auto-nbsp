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
    /**
     * Singleton instance of the class
     *
     * @var AutoNbsp|null
     */
    protected static ?AutoNbsp $instance = null;

    /**
     * Array of replacements for non-breaking spaces
     *
     * @var array
     */
    private array $replacements;

    /**
     * Non-breaking space character (or HTML entity)
     *
     * @var string
     */
    private string $nbsp;

    /**
     * Default replacement patterns for various languages and contexts
     *
     * @var array
     */
    private const DEFAULT_REPLACEMENTS = [
        '*' => [
            'prepositions_conjunctions' => [
                '&', '&amp;'
            ],
            'titles_before_name' => [
                'Bc.', 'BcA.', 'ing.', 'Ing.', 'Ing.arch.', 'MUDr.', 'MVDr.', 'MgA.', 'Mgr.', 'JUDr.', 'PhDr.', 'RNDr.', 'PharmDr.', 'ThLic.', 'ThDr.', 'prof.', 'doc.', 'PaedDr.', 'Dr.', 'PhMr.'
            ],
            'titles_after_name' => [
                'DiS.', 'MBA', 'Ph.D.', 'Th.D.', 'CSc.', 'DrSc.', 'dr. h. c.'
            ],
            'units' => [
                'm', 'g', 'l', 'q', 't', 'w', 'J', '%', 'ks', 'mm', 'cm', 'km', 'mg', 'dkg', 'kg', 'ml', 'cl', 'dl', 'hl', 'm³', 'km³', 'mm²', 'cm²', 'dm²', 'm²', 'km²', 'ha', 'Pa', 'hPa', 'kPa', 'MPa', 'bar', 'mbar', 'nbar', 'atm', 'psi', 'kW', 'MW', 'HP', 'm/s', 'km/h', 'm/min', 'MPH', 'cal', 'Wh', 'kWh', 'kp·m', '°C', '°F', 'kB', 'dB', 'MB', 'GB', 'kHz', 'MHz', 'Kč', '€', '%'
            ]
        ],
        'cs' => [
            'prepositions_conjunctions' => [
                'a', 'i', 'o', 'u', 'k', 's', 'v', 'z', 'by', 'co', 'či', 'do', 'je', 'ke', 'ku', 'na', 'no', 'od', 'po', 'se', 'ta', 'to', 've', 'za', 'ze', 'že', 'aby', 'byl', 'což', 'jen', 'když', 'kde', 'kdy', 'který', 'která', 'které', 'nad', 'pod', 'pro', 'před', 'při', 'tak'
            ],
            'abbreviations' => [
                'cca.', 'č.', 'čís.', 'čj.', 'čp.', 'fa', 'fě', 'fy', 'kupř.', 'mj.', 'např.', 'p.', 'P.', 'pí', 'Pí.', 'popř.', 'př.', 'přib.', 'přibl.', 'r.', 'sl.', 'str.', 'sv.', 'tj.', 'tzn.', 'tzv.', 'zvl.'
            ],
            'months' => [
                'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec',
                'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'
            ],
        ],
        'en' => [
            'articles' => [
                'a', 'an', 'the'
            ],
            'prepositions_conjunctions' => [
                'of', 'in', 'on', 'at', 'by', 'to', 'for', 'and', '&', 'but', 'or', 'nor', 'yet', 'so', 'if', 'as'
            ],
            'abbreviations' => [
                'i.e.', 'e.g.', 'vs.'
            ],
            'titles_before_name' => [
                'Mr.', 'Mrs.', 'Ms.'
            ],
            'months' => [
                'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december', 'a.m.', 'p.m.',
            ],
        ]
    ];

    /**
     * AutoNbsp constructor.
     *
     * @param string $language Language code (e.g., 'en', 'cs')
     * @param array $custom_replacements Custom replacements to merge with the default ones
     * @param bool $prepositions_conjunctions Flag to process prepositions and conjunctions
     * @param bool $articles Flag to process articles
     * @param bool $abbreviations Flag to process abbreviations
     * @param bool $titles Flag to process titles
     * @param bool $units Flag to process units
     * @param bool $months Flag to process months
     * @param bool $after_numbers Flag to process spaces after numbers
     * @param bool $between_numbers Flag to process spaces between numbers
     * @param bool $debug Flag to enable debug mode
     */
    public function __construct(
        private string $language = 'en',
        private array $custom_replacements = [],
        private bool $prepositions_conjunctions = true,
        private bool $articles = true,
        private bool $abbreviations = true,
        private bool $titles = true,
        private bool $units = false,
        private bool $months = true,
        private bool $after_numbers = true,
        private bool $between_numbers = true,
        private bool $debug = false
    ) {
        $this->replacements = array_merge(self::DEFAULT_REPLACEMENTS, $this->custom_replacements);
        $this->nbsp = $this->debug ? '<span style="background:red;">&nbsp;</span>' : '&nbsp;';
    }

    /**
     * Get the singleton instance of AutoNbsp.
     *
     * @param mixed ...$args Arguments to pass to the constructor
     * @return AutoNbsp The singleton instance
     */
    public static function getInstance(...$args): AutoNbsp
    {
        if (self::$instance === null) {
            self::$instance = new self(...$args);
        }

        return self::$instance;
    }

    /**
     * Convert an array of words to a regex pattern for matching
     *
     * @param array $words Array of words to include in the regex
     * @return string Regex pattern
     */
    private function arrayToRegex(array $words): string
    {
        return implode('|', array_map(fn ($str) => preg_quote($str, '/'), $words));
    }

    /**
     * Get the replacement patterns for a specific key and language.
     *
     * @param string|null $key The specific key of replacements (e.g., 'articles')
     * @param string|null $language The language code (e.g., 'en')
     * @return array The replacements for the specified key and language
     */
    public function getReplacements(string $key = null, string $language = null): array
    {
        if (!$key && !$language) {
            return $this->replacements;
        }

        if (!$key && $language) {
            $global = $this->replacements['*'] ?? [];
            $language_specific = $this->replacements[$language] ?? [];

            return array_merge($global, $language_specific);
        }

        $global = $this->replacements['*'][$key] ?? [];
        $language_specific = $this->replacements[$language ?: $this->language][$key] ?? [];

        return array_merge($global, $language_specific);
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
        return preg_replace($pattern, '$1' . $this->nbsp, $string);
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
        return preg_replace($pattern, $this->nbsp . '$1', $string);
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
        $pattern = '/(?<=\d)(\.?)\s+(\d)(?=[^>]*?(<|$))/';
        return preg_replace($pattern, '$1' . $this->nbsp . '$2', $string);
    }

    /**
     * Replace spaces before months with non-breaking spaces.
     *
     * @param string $string The input string
     * @return string The processed string
     */
    public function beforeMonths(string $string): string
    {
        $months = $this->getReplacements('months');
        $pattern = '/(?<=\d)(\.?)\s+(' . $this->arrayToRegex($months) . ')(?=[^>]*?(<|$))/ui';
        return preg_replace($pattern, '$1' . $this->nbsp . '$2', $string);
    }

    /**
     * Replace spaces before units with non-breaking spaces.
     *
     * @param string $string The input string
     * @return string The processed string
     */
    public function beforeUnits(string $string): string
    {
        $units = $this->getReplacements('units');
        $pattern = '/(\d+)\s+(' . $this->arrayToRegex($units) . ')(?!\w)(?=[^>]*?(<|$))/u';
        return preg_replace($pattern, '$1' . $this->nbsp . '$2', $string);
    }

    /**
     * Replace specified spaces with non-breaking spaces in the given string based on configuration.
     *
     * @param string $string The input string
     * @return string The processed string
     */
    public function replace(string $string): string
    {
        // spaces after words
        $after_words = array_merge(
            $this->prepositions_conjunctions ? $this->getReplacements('prepositions_conjunctions') : [],
            $this->articles ? $this->getReplacements('articles') : [],
            $this->titles ? $this->getReplacements('titles_before_name') : [],
            $this->abbreviations ? $this->getReplacements('abbreviations') : []
        );
        if ($after_words) {
            $string = $this->afterWords($string, $after_words);
        }

        // spaces before words
        if ($this->titles) {
            $before_words = $this->getReplacements('titles_after_name');
            $string = $this->beforeWords($string, $before_words);
        }

        // spaces between number and a month
        if ($this->months) {
            $string = $this->beforeMonths($string);
        }

        // spaces after a number
        if ($this->after_numbers) {
            $string = $this->afterNumbers($string);
        }

        // spaces between two numbers
        if ($this->between_numbers) {
            $string = $this->betweenNumbers($string);
        }

        // spaces between a number and a unit
        if ($this->units) {
            $string = $this->beforeUnits($string);
        }

        return $string;
    }
}
