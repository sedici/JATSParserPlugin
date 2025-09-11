<?php namespace PKP\components\forms\CitationStyles\Core\Renderers;

require_once __DIR__ . '/../Formatters/AbstractCitationFormatter.php';

use PKP\components\forms\CitationStyles\Core\Formatters\AbstractCitationFormatter;


class ApaReferencesRenderer {

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
     * Constructor for the APA Table Renderer
     * 
     * @param AbstractCitationFormatter $formatter The formatter for citation strings
     * @param string $absoluteXmlPath Path to the XML file with citation data
     * @param string $citationStyle The citation style to use
     * @param int $publicationId The publication ID
     * @param string $localeKey The locale key for translation
     */
    public function __construct(AbstractCitationFormatter $formatter, string $absoluteXmlPath, string $citationStyle, int $publicationId, string $localeKey) {
        $this->formatter = $formatter;
        $this->absoluteXmlPath = $absoluteXmlPath;
        $this->citationStyle = $citationStyle;
        $this->publicationId = $publicationId;
        $this->localeKey = $localeKey;
    }

    /**
     * Helper to escape HTML values.
     */
    private function esc(string $value): string { 
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); 
    }

    /**
     * Returns the opening HTML table tags with table headers
     * 
     * @return string HTML string containing table opening and headers
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
     * 
     * @param string $xrefId The cross-reference ID
     * @param array $data The data for the citation row
     * @return string HTML string containing the citation row
     */
    public function renderCitationRow(string $xrefId, array $data): string {
        $html = '';
        $references = isset($data['references']) && is_array($data['references']) ? $data['references'] : [];
        if (empty($references)) {
            // Safe fallback: single row with context and empty reference
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
     * Renders the citation options for a citation depending on the number of authors or if it is a 
     * custom citation (not default)
     * 
     * @param string $xrefId The cross-reference ID
     * @param array $data The data for the citation options
     * @return string HTML string containing the citation options
     */
    public function renderCitationOptions(string $xrefId, array $data): string {
        $citationOptions = [];
        $years = [];
        $references = isset($data['references']) && is_array($data['references']) ? $data['references'] : [];
        if (empty($references)) {
            // Fallback: use original raw text if there are no structured references
            $isDefault = ($data['status'] ?? 'default') === 'default';
            $customValue = $data['citationText'] ?? '';
            $citationText = trim($data['originalText'] ?? '');
            $yearsText = '';
            $numRows = 1;
            return $this->buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, (!$isDefault && $customValue && $customValue !== $citationText), $customValue);
        }
        
        foreach ($references as $ref) {
            $authors = $ref['authors'];
            $year = $authors['data_1']['year'];
            $years[] = $year;
            $authorCount = count($authors);
            
            if ($authorCount == 1) {
                $citationOptions[] = $this->formatter->formatSingleAuthorCitation($authors['data_1'], $year);
            } elseif ($authorCount == 2) {
                $citationOptions[] = $this->formatter->formatTwoAuthorsCitation($authors['data_1'], $authors['data_2'], $year);
            } else {
                $citationOptions[] = $this->formatter->formatMultipleAuthorsCitation($authors, $year);
            }
        }
        
        $separator = $this->formatter->getCitationSeparator();

        $citationText = implode($separator, $citationOptions);
        $yearsText = implode($separator, array_unique($years));
        
        // Determine if the citation style is default, custom or years
        $isDefault = $data['status'] === 'default';
        $customValue = isset($data['citationText']) ? $data['citationText'] : '';
        $isCustom = !$isDefault && $customValue && $customValue !== "($citationText)" && $customValue !== "($yearsText)";
        
        $numRows = count($references);

        return $this->buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue);
    }

    /**
     * Builds the select element (option menu) for citation options for every citation in the table.
     * Refactor: unified selection, avoids duplicates, consistently sanitizes and simplifies logic.
     *
     * @param int    $numRows       rowspan to apply (number of rows in the group)
     * @param string $xrefId        ID of the citation (xref)
     * @param string $citationText  Formatted text (authors)
     * @param string $yearsText     Years text (may match citationText)
     * @param bool   $isDefault     Indicates that the default option (authors) is selected
     * @param bool   $isCustom      Indicates that the custom option is selected
     * @param string $customValue   Custom value (without parentheses)
     */
    public function buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue): string {
        $citationOptVal = "(" . $citationText . ")";
        $yearsOptVal    = $yearsText !== '' ? "(" . $yearsText . ")" : '';
        $hasYearsDistinct = $yearsOptVal !== '' && $yearsOptVal !== $citationOptVal;

        // Determine selection
        $selectCitation = false;
        $selectYears = false;
        if ($isCustom) {
            // nothing; custom will be selected below
        } elseif ($hasYearsDistinct && $customValue === $yearsOptVal) {
            $selectYears = true;
        } elseif ($isDefault || $customValue === '' || $customValue === $citationOptVal) {
            $selectCitation = true;
        } else {
            // fallback if something unexpected came: use citation
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
        // Option authors/citation
        $optionsHtml .= '<option value="' . $this->esc($citationOptVal) . '"' . ($selectCitation ? ' selected' : '') . '>' . $this->esc($citationOptVal) . '</option>';
        // Option years (only if distinct)
        if ($hasYearsDistinct) {
            $optionsHtml .= '<option value="' . $this->esc($yearsOptVal) . '"' . ($selectYears ? ' selected' : '') . '>' . $this->esc($yearsOptVal) . '</option>';
        }
        // Option custom
        $customLabel = __('plugins.generic.jatsParser.citationtable.customtext');
        $optionsHtml .= '<option value="custom"' . ($isCustom ? ' selected' : '') . '>' . $this->esc($customLabel) . '</option>';

        $html = "<td rowspan='" . (int)$numRows . "' class='citation-td select-wrapper-cell'>";
        $html .= "<select name='citationStyle[{$this->esc($xrefId)}]' id='citationStyle_{$this->esc($xrefId)}' class='citation-select citation-original' data-original-value='" . $this->esc($originalValue) . "'>" . $optionsHtml . '</select>';

        if ($isCustom) {
            $customInputClass = 'custom-input citation-original';
            $html .= "<input type='text' name='customCitation[{$this->esc($xrefId)}]' id='customInput_{$this->esc($xrefId)}' value='" . $this->esc($customValue) . "' placeholder='e.g.: (González, 2011, p. 34)' class='" . $customInputClass . "' data-original-value='" . $this->esc($customValue) . "'>";
        }
        $html .= '</td>';
        return $html;
    }
}