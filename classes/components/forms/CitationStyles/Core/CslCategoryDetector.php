<?php namespace PKP\components\forms\CitationStyles\Core;

/**
 * CslCategoryDetector
 *
 * Detects the CSL format category ('author-date', 'numeric', 'note') for a given
 * citation style. Centralizes the logic that was previously duplicated/inlined in
 * CslReferencesRenderer and other places.
 *
 * Priority:
 *  1. Hardcoded map for well-known styles (fast path)
 *  2. Reading the <category citation-format="..."> attribute from the .csl XML file
 *  3. Fallback: 'author-date'
 */
class CslCategoryDetector {

    /**
     * Hardcoded map for well-known citation styles.
     * Keys must be lowercase.
     */
    private static $categoryMap = [
        // author-date styles
        'apa'                                        => 'author-date',
        'apa-spanish-sumarc'                         => 'author-date',
        'apa-no-ampersand_generic'                   => 'author-date',
        'associacao-brasileira-de-normas-tecnicas'   => 'author-date',
        'chicago-author-date'                        => 'author-date',
        'harvard-cite-them-right'                    => 'author-date',
        'modern-language-association'                => 'author-date',
        // numeric styles — the table should NOT appear for these
        'vancouver'                                  => 'numeric',
        'ieee'                                       => 'numeric',
        'acm-sig-proceedings'                        => 'numeric',
        'acs-nano'                                   => 'numeric',
        // note / footnote styles
        'turabian-fullnote-bibliography'             => 'note',
        'chicago-fullnote-bibliography'              => 'note',
    ];

    /**
     * Detect the CSL format category for a given citation style identifier or file path.
     *
     * @param string $citationStyle Style name (e.g. 'apa', 'ieee') or absolute path to a .csl file.
     * @return string 'author-date' | 'numeric' | 'note'
     */
    public static function detect(string $citationStyle): string {
        $lowerStyle = strtolower(trim($citationStyle));

        // 1. Fast path: hardcoded map
        if (isset(self::$categoryMap[$lowerStyle])) {
            return self::$categoryMap[$lowerStyle];
        }

        // 2. Try to read the category from the CSL XML file
        $cslPath = self::resolveCslPath($citationStyle);

        if ($cslPath !== null && file_exists($cslPath)) {
            $xml = @simplexml_load_file($cslPath);
            if ($xml) {
                // Check <category citation-format="..."/>
                if (isset($xml->info->category)) {
                    foreach ($xml->info->category as $category) {
                        $format = (string) $category['citation-format'];
                        if (!empty($format)) {
                            return $format;
                        }
                    }
                }
                // Check class="note" attribute on <style>
                if (isset($xml['class']) && (string) $xml['class'] === 'note') {
                    return 'note';
                }
            }
        }

        // 3. Default fallback
        return 'author-date';
    }

    /**
     * Returns true if the citation style has more than one in-text citation form,
     * meaning the citation assignment table should be shown to the editor.
     *
     * Numeric styles (IEEE, Vancouver, ACM, ACS) only produce one form (e.g. [1]),
     * so no table is needed for them.
     *
     * @param string $citationStyle
     * @return bool
     */
    public static function hasMultipleCitationForms(string $citationStyle): bool {
        return self::detect($citationStyle) !== 'numeric';
    }

    /**
     * Resolve the absolute filesystem path to the .csl file for a given style name.
     * Returns null if the file cannot be located.
     *
     * Search order:
     *  1. $citationStyle is already an absolute path → use directly
     *  2. Plugin's Back/CSL/ directory
     *  3. JATSParser\HTML\Document::CITATION_STYLES constant (if defined)
     *
     * @param string $citationStyle
     * @return string|null
     */
    public static function resolveCslPath(string $citationStyle): ?string {
        // Already an absolute/resolvable path
        if (file_exists($citationStyle)) {
            return $citationStyle;
        }

        $lowerStyle = strtolower(trim($citationStyle));

        // Special mapping: 'apa' uses the SUMARC-specific Spanish file
        $specialMap = [
            'apa' => 'apa-spanish-SUMARC',
        ];
        $fileBase = $specialMap[$lowerStyle] ?? $lowerStyle;

        // Plugin root is 5 levels up from this file's directory:
        // Core/ -> CitationStyles/ -> forms/ -> components/ -> classes/ -> (plugin root)
        $pluginRoot = __DIR__ . '/../../../../../';
        $candidate  = $pluginRoot . 'JATSParser/src/JATSParser/Back/CSL/' . $fileBase . '.csl';
        if (file_exists($candidate)) {
            return realpath($candidate);
        }

        // Fallback: check CITATION_STYLES constant from the HTML Document class
        if (
            class_exists('JATSParser\\HTML\\Document') &&
            defined('JATSParser\\HTML\\Document::CITATION_STYLES') &&
            array_key_exists($lowerStyle, \JATSParser\HTML\Document::CITATION_STYLES)
        ) {
            $mapped = \JATSParser\HTML\Document::CITATION_STYLES[$lowerStyle];
            if (file_exists($mapped)) {
                return $mapped;
            }
        }

        return null;
    }
}
