<?php namespace PKP\components\forms\CitationStyles\Core\Renderers;

require_once __DIR__ . '/../Formatters/AbstractCitationFormatter.php';
require_once __DIR__ . '/../FormValidators/ApaFormValidator.php';

use PKP\components\forms\CitationStyles\Core\Formatters\AbstractCitationFormatter;
use PKP\components\forms\CitationStyles\Core\FormValidators\ApaFormValidator;

/**
 * Class responsible for rendering APA citation tables in HTML format.
 * This class manages the generation of citation tables with APA formatting
 * including form structure, table layout, and citation options.
 */
class ApaTableRenderer {

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
     * Returns the opening HTML form tags with necessary hidden fields
     * 
     * @return string HTML string containing form opening and hidden fields
     */
    public function getFormOpening(): string {
        return '<form method="POST" target="_self" id="citationForm">
                <input type="hidden" name="xmlFilePath" value="' . htmlspecialchars($this->absoluteXmlPath) . '">
                <input type="hidden" name="citationStyleName" value="' . htmlspecialchars($this->citationStyle) . '">
                <input type="hidden" name="publicationId" value="' . htmlspecialchars($this->publicationId) . '">
                <input type="hidden" name="locale_key" value="' . htmlspecialchars($this->localeKey) . '">';
    }

    /**
     * Returns the closing HTML form tags
     * 
     * @return string HTML string containing closing form tags
     */
    public function getClosingForm(): string {
        return '</form></div>';
    }

    /**
     * Returns the opening HTML table tags with table headers
     * 
     * @return string HTML string containing table opening and headers
     */
    public function getTableHeader(): string {
        return '<table class="citation-table">
                <tr class="citation-header">
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlecontext') .'</th>
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlereferences') . '</th>
                    <th class="citation-th"> ' . __('plugins.generic.jatsParser.citationtable.titlecitationstyle') . ' </th>
                </tr>';
    }

    /**
     * Returns the closing HTML table tags
     * 
     * @return string HTML string containing table closing tags
     */
    public function getClosingTable() : string {
        return '</table>';
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
        $numRows = count($data['references']);
        $firstRow = true;
        
        $referenceKeys = array_keys($data['references']);
        $lastReferenceKey = end($referenceKeys);

        foreach ($data['references'] as $key => $referenceData) {
            $isLastRowInGroup = ($key === $lastReferenceKey);
            $rowClass = 'citation-row';
            if ($isLastRowInGroup) {
                $rowClass .= ' citation-group-last-row';
            }

            $html .= "<tr class='" . $rowClass . "'>";
            
            if ($firstRow) {
                $html .= '<td rowspan="' . $numRows . '" class="citation-td">' . $data['context'] . '</td>';
            }
            
            $html .= "<td class='citation-td'>" . $referenceData['reference'] . "</td>";
            
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
        
        foreach ($data['references'] as $ref) {
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
        
        $numRows = count($data['references']);

        return $this->buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue);
    }

    /**
     * Builds the select element (option menu) for citation options for every citation in the table 
     * 
     * @param string $xrefId The cross-reference ID
     * @param string $citationText The formatted citation text
     * @param string $yearsText The formatted years text
     * @param bool $isDefault Whether the citation style is default
     * @param bool $isCustom Whether the citation style is custom
     * @param string $customValue The custom citation value
     * @return string HTML string containing the select element
     */
    public function buildSelectElement($numRows, $xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue): string {
        $citationTextOption = "<option value='($citationText)' " . ($isDefault || (!$isDefault && $customValue === "($citationText)") ? "selected" : "") . ">($citationText)</option>";
        $yearsTextOption = "<option value='($yearsText)' " . (!$isDefault && $customValue === "($yearsText)" ? "selected" : "") . ">($yearsText)</option>";
        $customOption = "<option value='custom' " . ($isCustom ? "selected" : "") . ">" . __('plugins.generic.jatsParser.citationtable.customtext') . "</option>";

        $selectClass = 'citation-select citation-original';

        $html = "<td rowspan='{$numRows}' class='citation-td select-wrapper-cell'>
                    <select name='citationStyle[{$xrefId}]' id='citationStyle_{$xrefId}' 
                        class='{$selectClass}'
                        data-original-value='" . ($isCustom ? "custom" : ($isDefault || (!$isDefault && $customValue === "($citationText)") ? "($citationText)" : "($yearsText)")) . "'>
                        {$citationTextOption}
                        {$yearsTextOption}
                        {$customOption}
                    </select>";
        if ($isCustom) {
            $customInputClass = "custom-input" . ($customValue ? " citation-original" : "");
            $html .= "<input type='text' name='customCitation[{$xrefId}]' id='customInput_{$xrefId}' 
                          value='" . htmlspecialchars($customValue) . "' 
                          placeholder='ej: (González, 2011, p. 34)' 
                          class='{$customInputClass}'
                          data-original-value='" . htmlspecialchars($customValue) . "'>";
        }
        $html .= "</td>";
        return $html;
    }

}