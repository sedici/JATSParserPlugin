<?php namespace PKP\components\forms\CitationStyles\Core\Renderers;

require_once __DIR__ . '/../Formatters/AbstractCitationFormatter.php';
require_once __DIR__ . '/../CslCategoryDetector.php';

use PKP\components\forms\CitationStyles\Core\Formatters\AbstractCitationFormatter;
use PKP\components\forms\CitationStyles\Core\CslCategoryDetector;
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

class CslReferencesRenderer {

    /**
     * Citation formatter instance used to format citation strings
     * @var AbstractCitationFormatter
     */
    public $formatter;

    /**
     * Absolute path to the XML file containing citation data
     * @var string
     */
    public $absoluteXmlPath;

    /**
     * The selected citation style being used
     * @var string
     */
    public $citationStyle;

    /**
     * The ID of the publication that has the citations
     * @var int
     */
    public $publicationId;

    /**
     * The locale key used for internationalization
     * @var string
     */
    public $localeKey;

    /**
     * Detected CSL format category: 'author-date', 'numeric', 'note'
     * @var string
     */
    private $cslFormatCategory;

    /**
     * Cached CiteProc instance
     * @var CiteProc|null
     */
    private $citeProc = null;

    /**
     * Constructor for the CSL References Renderer
     */
    public function __construct(AbstractCitationFormatter $formatter, string $absoluteXmlPath, string $citationStyle, int $publicationId, string $localeKey) {
        $this->formatter = $formatter;
        $this->absoluteXmlPath = $absoluteXmlPath;
        $this->citationStyle = $citationStyle;
        $this->publicationId = $publicationId;
        $this->localeKey = $localeKey;
        // Delegate category detection to the shared utility class
        $this->cslFormatCategory = CslCategoryDetector::detect($citationStyle);
    }

    /**
     * Lazily initialize CiteProc instance for stylesheet-driven CSL citation rendering.
     *
     * Loading order:
     *  1. If CslCategoryDetector resolves a local .csl file (e.g. 'apa' → apa-spanish-SUMARC.csl,
     *     or any custom style placed in Back/CSL/), use that file.
     *  2. Otherwise, let citeproc-php load the style by name from its bundled style repository
     *     (this covers harvard-cite-them-right, associacao-brasileira-de-normas-tecnicas, etc.)
     */
    public function getCiteProc(): ?CiteProc {
        if ($this->citeProc !== null) {
            return $this->citeProc;
        }

        if (!class_exists('Seboettg\\CiteProc\\StyleSheet') || !class_exists('Seboettg\\CiteProc\\CiteProc')) {
            error_log('[CslRenderer] ERROR: StyleSheet or CiteProc class not found');
            return null;
        }

        $lang = str_replace('_', '-', $this->localeKey);

        try {
            // 1. Try a locally resolved file first (custom CSL files, apa → SUMARC mapping)
            $cslPath = CslCategoryDetector::resolveCslPath($this->citationStyle);

            if ($cslPath && file_exists($cslPath)) {
                error_log('[CslRenderer] getCiteProc: loading from local file: ' . $cslPath);
                $style = StyleSheet::loadStyleSheet($cslPath);
            } else {
                // 2. Fall back to citeproc-php bundled styles (harvard, abnt, chicago-ad, etc.)
                //    StyleSheet::loadStyleSheet() accepts a style name and resolves it internally.
                error_log('[CslRenderer] getCiteProc: loading by name from bundled styles: ' . $this->citationStyle);
                $style = StyleSheet::loadStyleSheet($this->citationStyle);
            }

            $this->citeProc = new CiteProc($style, $lang);
            error_log('[CslRenderer] getCiteProc: CiteProc initialized OK for style=' . $this->citationStyle);
        } catch (\Throwable $e) {
            error_log('[CslRenderer] getCiteProc: EXCEPTION for style=' . $this->citationStyle . ' → ' . $e->getMessage());
            $this->citeProc = null;
        }

        return $this->citeProc;
    }

    /**
     * Render in-text citation dynamically through CiteProc engine according to active CSL file
     */
    public function renderCslCitationForXref(array $references): ?string {
        if (!class_exists('JATSParser\\HTML\\Reference') || !class_exists('JATSParser\\Body\\Document')) {
            error_log('[CslRenderer] renderCslCitationForXref: JATSParser classes not found');
            return null;
        }

        $citeProc = $this->getCiteProc();
        if (!$citeProc) {
            error_log('[CslRenderer] renderCslCitationForXref: CiteProc is null, using formatter fallback');
            return null;
        }

        try {
            $jatsDoc = new \JATSParser\Body\Document($this->absoluteXmlPath);
            $jatsRefs = $jatsDoc->getReferences();
            $jatsRefsById = [];
            foreach ($jatsRefs as $ref) {
                $jatsRefsById[$ref->getId()] = $ref;
            }

            $cslItems = [];
            foreach ($references as $refId => $refData) {
                $targetId = is_string($refId) ? $refId : ($refData['id'] ?? null);
                if ($targetId && isset($jatsRefsById[$targetId])) {
                    $cslRef = new \JATSParser\HTML\Reference($jatsRefsById[$targetId]);
                    if (!$cslRef->refIsEmpty()) {
                        $cslItems[] = $cslRef->getContent();
                    } else {
                        error_log('[CslRenderer] renderCslCitationForXref: refIsEmpty() for id=' . $targetId);
                    }
                } else {
                    error_log('[CslRenderer] renderCslCitationForXref: ref not found in jatsDoc for targetId=' . $targetId . ' available=' . implode(',', array_keys($jatsRefsById)));
                }
            }

            if (empty($cslItems)) {
                error_log('[CslRenderer] renderCslCitationForXref: cslItems empty, returning null');
                return null;
            }

            $rendered = $citeProc->render($cslItems, "citation");
            error_log('[CslRenderer] renderCslCitationForXref: rendered=' . $rendered);
            return trim(strip_tags($rendered));
        } catch (\Throwable $e) {
            error_log('[CslRenderer] renderCslCitationForXref: EXCEPTION → ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return null;
        }
    }

    /**
     * Detect CSL format category — delegates to CslCategoryDetector.
     * Kept for backwards compatibility with any external callers.
     *
     * @deprecated Use CslCategoryDetector::detect() directly.
     */
    public static function detectCslFormatCategory(string $citationStyle): string {
        return CslCategoryDetector::detect($citationStyle);
    }

    /**
     * Helper to escape HTML values.
     */
    private function esc(string $value): string { 
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); 
    }

    /**
     * Returns the opening HTML table tags with table headers
     */
    public function getTableHeader(): string {
        return '<table class="citation-table">
                <tr class="citation-header">
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlecontext') . '</th>
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlereferences') . '</th>
                    <th class="citation-th"> ' . __('plugins.generic.jatsParser.citationtable.titlecitationstyle') . ' </th>
                </tr>';
    }

    /**
     * Renders a row of citations in the table
     */
    public function renderCitationRow(string $xrefId, array $data): string {
        $html = '';
        $references = isset($data['references']) && is_array($data['references']) ? $data['references'] : [];
        if (empty($references)) {
            $references = [['reference' => $data['reference'] ?? ($data['title'] ?? '') , 'authors' => $data['authors'] ?? []]];
        }
        $numRows = count($references);
        $firstRow = true;
        
        $referenceKeys = array_keys($references);
        $lastReferenceKey = end($referenceKeys);

        foreach ($references as $key => $referenceData) {
            $isLastRowInGroup = ($key === $lastReferenceKey);
            $rowClass = 'citation-row';
            if ($isLastRowInGroup) {
                $rowClass .= ' citation-group-last-row';
            }

            $html .= "<tr class='" . $rowClass . "'>";
            
            if ($firstRow) {
                $html .= '<td rowspan="' . $numRows . '" class="citation-td">' . ($data['context'] ?? '') . '</td>';
            }
            
            $html .= "<td class='citation-td'>" . ($referenceData['reference'] ?? '') . "</td>";
            
            if ($firstRow) {
                $html .= $this->renderCitationOptions($xrefId, $data);
                $firstRow = false;
            }
            
            $html .= '</tr>';
        }
        
        return $html;
    }

    /**
     * Renders citation options for a citation depending on the CSL format category
     */
    public function renderCitationOptions(string $xrefId, array $data): string {
        $references = isset($data['references']) && is_array($data['references']) ? $data['references'] : [];
        $numRows = max(count($references), 1);
        $isDefault = ($data['status'] ?? 'default') === 'default';
        $customValue = isset($data['citationText']) ? $data['citationText'] : '';

        // 1. Numeric Styles (IEEE, Vancouver, ACM, ACS)
        if ($this->cslFormatCategory === 'numeric') {
            $origText = trim($data['originalText'] ?? '');
            if ($origText !== '') {
                $citationText = $origText;
            } else {
                $refNums = [];
                foreach ($references as $rId => $rVal) {
                    if (preg_match('/\d+/', (string)$rId, $m)) {
                        $refNums[] = $m[0];
                    }
                }
                $citationText = !empty($refNums) ? '[' . implode(', ', $refNums) . ']' : '[1]';
            }
            $yearsText = '';
            $isCustom = !$isDefault && $customValue !== '' && $customValue !== $citationText;
            return $this->buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue);
        }

        // 2. Footnote Styles (Turabian, Chicago Notes)
        if ($this->cslFormatCategory === 'note') {
            $origText = trim($data['originalText'] ?? '');
            $citationText = $origText !== '' ? $origText : '¹';
            $yearsText = '';
            $isCustom = !$isDefault && $customValue !== '' && $customValue !== $citationText;
            return $this->buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue);
        }

        // 3. Author-Date Styles (APA, ABNT, Harvard, Chicago Author-Date, MLA)
        // Try rendering dynamically via CiteProc with the active CSL stylesheet
        $cslRendered = $this->renderCslCitationForXref($references);
        if ($cslRendered !== null && $cslRendered !== '') {
            $citationText = $cslRendered;
            if (strpos($citationText, '(') === 0 && substr($citationText, -1) === ')') {
                $citationText = substr($citationText, 1, -1);
            }
            // Extract years for narrative option
            $years = [];
            foreach ($references as $ref) {
                $authors = isset($ref['authors']) && is_array($ref['authors']) ? $ref['authors'] : [];
                $year = isset($authors['data_1']['year']) ? $authors['data_1']['year'] : '';
                if ($year !== '') {
                    $years[] = $year;
                }
            }
            $yearsText = implode(', ', array_unique(array_filter($years)));
        } else {
            // Fallback formatting if CiteProc instance is unavailable
            $citationOptions = [];
            $years = [];
            
            if (empty($references)) {
                $citationText = trim($data['originalText'] ?? '');
                $yearsText = '';
                $isCustom = !$isDefault && $customValue !== '' && $customValue !== $citationText;
                return $this->buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue);
            }
            
            foreach ($references as $ref) {
                $authors = isset($ref['authors']) && is_array($ref['authors']) ? $ref['authors'] : [];
                $year = isset($authors['data_1']['year']) ? $authors['data_1']['year'] : '';
                if ($year !== '') {
                    $years[] = $year;
                }
                $authorCount = count($authors);
                
                if ($authorCount == 1) {
                    $citationOptions[] = $this->formatter->formatSingleAuthorCitation($authors['data_1'], $year);
                } elseif ($authorCount == 2) {
                    $citationOptions[] = $this->formatter->formatTwoAuthorsCitation($authors['data_1'], $authors['data_2'], $year);
                } elseif ($authorCount > 2) {
                    $citationOptions[] = $this->formatter->formatMultipleAuthorsCitation($authors, $year);
                } else {
                    $citationOptions[] = trim($data['originalText'] ?? '');
                }
            }
            
            $separator = $this->formatter->getCitationSeparator();
            $citationText = implode($separator, array_filter($citationOptions));
            if ($citationText === '') {
                $citationText = trim($data['originalText'] ?? '');
            }
            $yearsText = implode($separator, array_unique(array_filter($years)));
        }
        
        $isCustom = !$isDefault && $customValue !== '' && $customValue !== "($citationText)" && $customValue !== "($yearsText)" && $customValue !== $citationText;
        
        return $this->buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue);
    }

    /**
     * Builds the select element based on CSL format category ('author-date', 'numeric', 'note')
     */
    public function buildSelectElement(int $numRows, string $xrefId, string $citationText, string $yearsText, bool $isDefault, bool $isCustom, string $customValue): string {
        if ($this->cslFormatCategory === 'author-date') {
            $citationOptVal = (strpos($citationText, '(') === 0) ? $citationText : "(" . $citationText . ")";
            $yearsOptVal    = $yearsText !== '' ? ((strpos($yearsText, '(') === 0) ? $yearsText : "(" . $yearsText . ")") : '';
            $hasYearsDistinct = $yearsOptVal !== '' && $yearsOptVal !== $citationOptVal;
        } else {
            // Numeric or Note styles: use citationText as is (e.g. "[1]" or "1")
            $citationOptVal = $citationText;
            $yearsOptVal = '';
            $hasYearsDistinct = false;
        }

        // Determine selection
        $selectCitation = false;
        $selectYears = false;
        if ($isCustom) {
            // custom is selected
        } elseif ($hasYearsDistinct && $customValue === $yearsOptVal) {
            $selectYears = true;
        } elseif ($isDefault || $customValue === '' || $customValue === $citationOptVal) {
            $selectCitation = true;
        } else {
            $selectCitation = true;
        }

        // Original value for data-original-value
        if ($isCustom) {
            $originalValue = 'custom';
        } elseif ($selectYears) {
            $originalValue = $yearsOptVal;
        } else {
            $originalValue = $citationOptVal;
        }

        $optionsHtml = '';
        // Option 1: Standard Citation
        $optionsHtml .= '<option value="' . $this->esc($citationOptVal) . '"' . ($selectCitation ? ' selected' : '') . '>' . $this->esc($citationOptVal) . '</option>';
        
        // Option 2: Years / Narrative (Only for author-date styles)
        if ($hasYearsDistinct) {
            $optionsHtml .= '<option value="' . $this->esc($yearsOptVal) . '"' . ($selectYears ? ' selected' : '') . '>' . $this->esc($yearsOptVal) . '</option>';
        }
        
        // Option 3: Custom
        $customLabel = __('plugins.generic.jatsParser.citationtable.customtext');
        $optionsHtml .= '<option value="custom"' . ($isCustom ? ' selected' : '') . '>' . $this->esc($customLabel) . '</option>';

        $html = "<td rowspan='" . (int)$numRows . "' class='citation-td select-wrapper-cell'>";
        $html .= "<select name='citationStyle[{$this->esc($xrefId)}]' id='citationStyle_{$this->esc($xrefId)}' class='citation-select citation-original' data-original-value='" . $this->esc($originalValue) . "'>" . $optionsHtml . '</select>';

        if ($isCustom) {
            $customInputClass = 'custom-input citation-original';
            $html .= "<input type='text' name='customCitation[{$this->esc($xrefId)}]' id='customInput_{$this->esc($xrefId)}' value='" . $this->esc($customValue) . "' placeholder='e.g.: [1, p. 15]' class='" . $customInputClass . "' data-original-value='" . $this->esc($customValue) . "'>";
        }
        $html .= '</td>';
        return $html;
    }
}
