# Changelog

## [1.2.0] - 2026-08-07
### Added
- Replacement rules:
  - Non-breaking spaces around hyphen, en dash, and em dash numeric ranges
  - Configurable `nameInitials` rule for keeping initials with surnames
  - Configurable `symbolsBeforeNumbers` rule for keeping `§` and `#` with following numbers
  - Configurable, language-aware `ordinals` rule for keeping ordinal numbers with following words
  - Configurable `compoundAbbreviations` rule for keeping compound abbreviations and initial groups together
  - Configurable `ratios` rule for keeping ratios and scales together
- Replacement data:
  - Czech abbreviations, academic titles, and military ranks
  - Dotted and undotted English titles, suffixes, credentials, labels, abbreviations, and `am`/`pm` time markers
  - Slovak short prepositions and conjunctions, abbreviations, titles, months, and ordinals
  - German articles, short prepositions and conjunctions, abbreviations, titles, months, and ordinals
  - Common SI, digital, typographic, currency, and related global units and symbols
- Language and replacement architecture:
  - Lazy-loaded files in `replacements/`, with one file per language and a shared `global.php`
  - Single-language fallback through Kirby's global `language` option
- Integrations and documentation:
  - Support for applying the Latte `nbsp` filter to HTML content while preserving its content type
  - README documentation for usage, options, custom replacements, the `nbsp()` helper, and the Barista filter

### Changed
- **Breaking API changes:**
  - Renamed constructor parameters, variables, and replacement array keys from snake_case to camelCase
  - Replaced individual constructor rule arguments with a partial camelCase `rules` array
- **Potentially breaking defaults:**
  - Enabled unit replacements by default
  - Enabled the new `nameInitials`, `symbolsBeforeNumbers`, `compoundAbbreviations`, and `ratios` rules by default
  - Enabled the new Czech `ordinals` rule by default
- Filtering behavior:
  - The Latte filter now processes text before sanitizing it with Barista's `safe_html()` helper and returns literal `&nbsp;` entities
  - Multi-word titles before and after names are kept together as single non-breaking units
  - Unsupported languages now use only global replacements
  - Ordinal rules can configure lowercase and uppercase following words per language
- Architecture and performance:
  - Formatter instances are cached by the Kirby helper instead of a singleton in the core class
  - Generated regex alternatives are memoized per formatter instance
  - Strings without whitespace return immediately, and number-dependent rules are skipped for strings without digits

### Fixed
- Replacement merging and data:
  - Custom replacements extend built-in replacement groups instead of replacing a language's complete map
  - Complete replacement maps merge global and language-specific values group by group
  - The watt symbol uses the correct uppercase `W`, and the duplicate `%` unit was removed
  - The English replacement list no longer duplicates the global ampersand
- Pattern matching:
  - Month replacements no longer match prefixes of longer words
  - Empty language-specific month lists no longer create an empty regular-expression alternative
- Integrations and language handling:
  - The Latte filter no longer rejects captured HTML blocks as an incompatible content type
  - Single-language installations no longer fail when resolving the current language


## [1.1.0] - 2026-06-03
### Added
- `$language` parameter to `nbsp()` helper function, field method and latte filter


## [1.0.2] - 2024-08-09
### Added
- "až" to Czech conjunctions


## [1.0.1] - 2024-07-03
### Added
- ampersand to global replacements

### Fixed
- debug enabled by default


## [1.0.0] - 2024-05-28
### Added
- Initial release
