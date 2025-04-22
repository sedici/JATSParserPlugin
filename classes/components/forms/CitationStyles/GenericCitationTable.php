<?php namespace PKP\components\forms\CitationStyles;

require_once __DIR__ . '/../Helpers/process_citations.php';

//Stylesheet import
require_once __DIR__ . '/Stylesheets/ApaStylesheet.php';
use PKP\components\forms\CitationStyles\Stylesheets\ApaStylesheet;

abstract class GenericCitationTable{

/* Example of expected array structure:
$arrayData = [
    'xref_id1' => [
        'status' => 'default',
        'citationText' => '',
        'context' => 'Context 1',
        'rid' => 'parser_0 parser_1',
        'references' => [
            [
                'id' => 'parser_0',
                'reference' => 'Reference 1',
                'authors' => [
                    'data_1' => [
                        'surname' => 'Smith',
                        'year' => '2020'
                    ],
                    'data_2' => [
                        'surname' => 'Johnson',
                        'year' => '2020'
                    ]
                ]
            ],
            [
                'id' => 'parser_1',
                'reference' => 'Reference 2',
                'authors' => [
                    'data_1' => [
                        'surname' => 'Doe',
                        'year' => '2019'
                    ]
                ]
            ]
        ]
    ],
    'xref_id2' => [
        'status' => 'not-default',
        'citationText' => '(Smith y Johnson, 2020; Doe et al, 2019)',
        'context' => 'Context 2',
        'rid' => 'parser_1',
        'references' => [
            [
                'id' => 'parser_1',
                'reference' => 'Reference 1',
                'authors' => [
                    'data_1' => [
                        'surname' => 'Doe',
                        'year' => '2019'
                    ]
                ]
            ]
        ]
    ]
];
*/

    protected $arrayData;
    protected $absoluteXmlPath;
    protected $citationStyle;
    protected $publicationId;
    protected $localeKey;

    public function __construct(array $arrayData, string $absoluteXmlPath, string $citationStyle, int $publicationId, string $localeKey) {
        $this->arrayData = $arrayData;
        $this->absoluteXmlPath = $absoluteXmlPath;
        $this->citationStyle = $citationStyle;
        $this->publicationId = $publicationId;
        $this->localeKey = $localeKey;
    }

    //public method to generate table HTML
    public function makeHtml() {
        
        // Check if arrayData is empty.   
        if (empty($this->arrayData)) {
            return $this->getEmptyCitationsMessage();
        }

        $html = '<div class="citation-form-container">';
        $html .= $this->getErrorMessageHtml();
        $html .= $this->getFormOpeningHtml();
        $html .= $this->getTableHeader();
        $html .= $this->renderTableRows();
        $html .= $this->getFormSaveButton();
        $html .= ApaStylesheet::getStyles(); // Include the styles for the citation table
        
        return $html;
    }

    protected abstract function formatSingleAuthorCitation(array $author, string $year): string;
    protected abstract function formatTwoAuthorsCitation(array $author1, array $author2, string $year): string;
    protected abstract function formatMultipleAuthorsCitation(array $authors, string $year): string;
    protected abstract function getCitationSeparator(): string;

    protected function getEmptyCitationsMessage(): string {
        return "<div><span>" . __('plugins.generic.citationtable.citations.notfound') . "</span></div>";
    }    

    protected function getErrorMessageHtml(): string {
        return '<div id="citationErrorMessage" class="citation-error-message" style="display:none;">
                <span class="error-icon">⚠️</span> ' . __('plugins.generic.jatsParser.citationtable.error.emptyoption') . '.
                </div>';
    }

    protected function getFormOpeningHtml(): string {
        return '<form method="POST" target="_self" id="citationForm" onsubmit="' . $this->onSubmitFunctions() . '">
                <input type="hidden" name="xmlFilePath" value="' . htmlspecialchars($this->absoluteXmlPath) . '">
                <input type="hidden" name="citationStyleName" value="' . htmlspecialchars($this->citationStyle) . '">
                <input type="hidden" name="publicationId" value="' . htmlspecialchars($this->publicationId) . '">
                <input type="hidden" name="locale_key" value="' . htmlspecialchars($this->localeKey) . '">';
    }

    protected function getTableHeader(): string {
        return '<table class="citation-table">
                <tr class="citation-header">
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlecontext') .'</th>
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlereferences') . '</th>
                    <th class="citation-th"> ' . __('plugins.generic.jatsParser.citationtable.titlecitationstyle') . ' </th>
                </tr>';
    }

    protected function renderTableRows(): string {
        $html = '';
        
        foreach ($this->arrayData as $xrefId => $data) {
            $html .= $this->renderCitationRow($xrefId, $data);
        }
        
        return $html;
    }

    protected function renderCitationRow(string $xrefId, array $data): string {
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

    protected function renderCitationOptions(string $xrefId, array $data): string {
        $citationOptions = [];
        $years = [];
        
        foreach ($data['references'] as $ref) {
            $authors = $ref['authors'];
            $year = $authors['data_1']['year'];
            $years[] = $year;
            $authorCount = count($authors);
            
            if ($authorCount == 1) {
                $citationOptions[] = $this->formatSingleAuthorCitation($authors['data_1'], $year);
            } elseif ($authorCount == 2) {
                $citationOptions[] = $this->formatTwoAuthorsCitation($authors['data_1'], $authors['data_2'], $year);
            } else {
                $citationOptions[] = $this->formatMultipleAuthorsCitation($authors, $year);
            }
        }
        
        $separator = $this->getCitationSeparator();
        $citationText = implode($separator, $citationOptions);
        $yearsText = implode($separator, array_unique($years));
        
        // Determine if the citation style is default, custom or years
        $isDefault = $data['status'] === 'default';
        $customValue = isset($data['citationText']) ? $data['citationText'] : '';
        $isCustom = !$isDefault && $customValue && $customValue !== "($citationText)" && $customValue !== "($yearsText)";
        
        return $this->buildSelectElement($xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue);
    }

    protected function buildSelectElement($xrefId, $citationText, $yearsText, $isDefault, $isCustom, $customValue): string {
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

    protected function getFormSaveButton() {
        return '</table>
                    <button type="submit" class="save-btn-citations">
                        ' . __('plugins.generic.jatsParser.citationtable.savebuttontext') . '
                    </button>
                </form>
                </div>';
    }

    protected function onSubmitFunctions() {
        return 'let customInputs = document.querySelectorAll(\'.custom-input\');
                let hasEmptyCustomFields = false;
                            
                customInputs.forEach(function(input) {
                    if(input.value.trim() === \'\') {
                        hasEmptyCustomFields = true;
                        input.classList.add(\'citation-select-error\');
                    } else {
                        input.classList.remove(\'citation-select-error\');
                    }
                });
                            
                if(hasEmptyCustomFields) {
                    document.getElementById(\'citationErrorMessage\').style.display = \'block\';
                    return false;
                } else {
                    document.getElementById(\'citationErrorMessage\').style.display = \'none\';
                    window.location.reload(true);
                    let form = this;
                    let formData = new FormData(form);

                    fetch(\'./process_citations.php\', {
                        method: \'POST\',
                        body: formData
                    });
                }';
    }

}