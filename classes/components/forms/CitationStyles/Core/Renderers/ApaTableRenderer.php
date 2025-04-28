<?php namespace PKP\components\forms\CitationStyles\Core\Renderers;

require_once __DIR__ . '/../Formatters/AbstractCitationFormatter.php';
require_once __DIR__ . '/../FormValidators/ApaFormValidator.php';

use PKP\components\forms\CitationStyles\Core\Formatters\AbstractCitationFormatter;
use PKP\components\forms\CitationStyles\Core\FormValidators\ApaFormValidator;

class ApaTableRenderer {

    public $formatter;
    public $absoluteXmlPath;
    public $citationStyle;
    public $publicationId;
    public $localeKey;

    public function __construct(AbstractCitationFormatter $formatter, string $absoluteXmlPath, string $citationStyle, int $publicationId, string $localeKey) {
        $this->formatter = $formatter;
        $this->absoluteXmlPath = $absoluteXmlPath;
        $this->citationStyle = $citationStyle;
        $this->publicationId = $publicationId;
        $this->localeKey = $localeKey;
    }

    public function getFormOpeningHtml(): string {
        return '<form method="POST" target="_self" id="citationForm" onsubmit="' . ApaFormValidator::onSubmitFunctions() . '">
                <input type="hidden" name="xmlFilePath" value="' . htmlspecialchars($this->absoluteXmlPath) . '">
                <input type="hidden" name="citationStyleName" value="' . htmlspecialchars($this->citationStyle) . '">
                <input type="hidden" name="publicationId" value="' . htmlspecialchars($this->publicationId) . '">
                <input type="hidden" name="locale_key" value="' . htmlspecialchars($this->localeKey) . '">';
    }

    public function getTableHeader(): string {
        return '<table class="citation-table">
                <tr class="citation-header">
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlecontext') .'</th>
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlereferences') . '</th>
                    <th class="citation-th"> ' . __('plugins.generic.jatsParser.citationtable.titlecitationstyle') . ' </th>
                </tr>';
    }

    public function renderCitationRow(string $xrefId, array $data): string {
        $html = '';
        $numRows = count($data['references']);
        $firstRow = true;
        
        foreach ($data['references'] as $referenceData) {
            $html .= "<tr class='citation-row'>";
            
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
        
        return $this->buildSelectElement($xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue);
    }

    public function buildSelectElement($xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue): string {
        // Construct the options for the select element
        $citationTextOption = "<option value='($citationText)' " . ($isDefault || (!$isDefault && $customValue === "($citationText)") ? "selected" : "") . ">($citationText)</option>";
        $yearsTextOption = "<option value='($yearsText)' " . (!$isDefault && $customValue === "($yearsText)" ? "selected" : "") . ">($yearsText)</option>";
        $customOption = "<option value='custom' " . ($isCustom ? "selected" : "") . ">" . __('plugins.generic.jatsParser.citationtable.customtext') . "</option>";

        // Apply styles to the select element based on the selected option
        $selectClass = 'citation-select citation-original';
        
        $html = "<td rowspan='1' class='citation-td'>
                    <select name='citationStyle[{$xrefId}]' id='citationStyle_{$xrefId}' 
                        class='{$selectClass}'
                        data-original-value='" . ($isCustom ? "custom" : ($isDefault || (!$isDefault && $customValue === "($citationText)") ? "($citationText)" : "($yearsText)")) . "'
                        onchange='
                            let selectElem = this;
                            let inputField = document.getElementById(\"customInput_{$xrefId}\");
                            
                            // Aplicar estilos según el cambio
                            if(selectElem.value !== selectElem.getAttribute(\"data-original-value\")) {
                                selectElem.classList.remove(\"citation-original\");
                                selectElem.classList.add(\"citation-modified\");
                            } else {
                                selectElem.classList.remove(\"citation-modified\");
                                selectElem.classList.add(\"citation-original\");
                            }
                            
                            if(selectElem.value == \"custom\"){
                                if(!inputField){
                                    inputField = document.createElement(\"input\");
                                    inputField.type = \"text\";
                                    inputField.name = \"customCitation[{$xrefId}]\";
                                    inputField.id = \"customInput_{$xrefId}\";
                                    inputField.placeholder = \"ej: (González, 2011, p. 34)\";
                                    inputField.className = \"custom-input\";
                                    inputField.onchange = function() {
                                        if(this.value.trim() === \"\") {
                                            this.classList.add(\"citation-select-error\");
                                        } else {
                                            this.classList.remove(\"citation-select-error\");
                                        }
                                    };
                                    selectElem.parentNode.appendChild(inputField);
                                }
                            } else {
                                if(inputField){
                                    inputField.remove();
                                }
                            }
                        '>
                        {$citationTextOption}
                        {$yearsTextOption}
                        {$customOption}
                    </select>";
        
        // If the option is custom, show the input field and apply styles based on the value
        if ($isCustom) {
            $customInputClass = "custom-input" . ($customValue ? " citation-original" : "");
            $html .= "<input type='text' name='customCitation[{$xrefId}]' id='customInput_{$xrefId}' 
                          value='" . htmlspecialchars($customValue) . "' 
                          placeholder='ej: (González, 2011, p. 34)' 
                          class='{$customInputClass}'
                          data-original-value='" . htmlspecialchars($customValue) . "'
                          onchange=\"
                            if(this.value.trim() === '') {
                                this.classList.add('citation-select-error');
                            } else {
                                this.classList.remove('citation-select-error');
                                if(this.value !== this.getAttribute('data-original-value')) {
                                    this.classList.remove('citation-original');
                                    this.classList.add('citation-modified');
                                } else {
                                    this.classList.remove('citation-modified');
                                    this.classList.add('citation-original');
                                }
                            }
                          \">";
        }
        
        $html .= "</td>";
        return $html;
    }

    public function getFormSaveButton() {
        return '</table>
                    <button type="submit" class="save-btn-citations">
                        ' . __('plugins.generic.jatsParser.citationtable.savebuttontext') . '
                    </button>
                </form>
                </div>';
    }

    public function getEmptyCitationsMessage(): string {
        return "<div><span>" . __('plugins.generic.citationtable.citations.notfound') . "</span></div>";
    }
}