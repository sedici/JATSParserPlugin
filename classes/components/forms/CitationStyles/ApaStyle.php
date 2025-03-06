<?php namespace PKP\components\forms\CitationStyles;

require_once __DIR__ . '/../Helpers/process_citations.php';

class ApaStyle{

/* Example of expected array structure:
$arrayData = [
    'xref_id1' => [
        'status' => 'default',
        citationText => '',
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

    CONST DELAY_TIME = 0.5;

    public static function makeHtml(Array $arrayData, String $absoluteXmlPath, String $citationStyle, int $publicationId) {
        
        $tableHTML = '<div class="citation-form-container">
                        <div id="citationErrorMessage" class="citation-error-message" style="display:none;">
                            <span class="error-icon">⚠️</span> Error: No puedes guardar una cita vacía.
                        </div>
                        <form method="POST" target="_self" id="citationForm" onsubmit="
                            // Verificar campos personalizados vacíos
                            let customInputs = document.querySelectorAll(\'.custom-input\');
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
                            }
                        ">

                        <input type="hidden" name="xmlFilePath" value="' . htmlspecialchars($absoluteXmlPath) . '">
                        <input type="hidden" name="citationStyleName" value="' . htmlspecialchars($citationStyle) . '">
                        <input type="hidden" name="publicationId" value="' . htmlspecialchars($publicationId) . '">
                        <table class="citation-table">
                            <tr class="citation-header">
                                <th class="citation-th">Contexto</th>
                                <th class="citation-th">Referencias</th>
                                <th class="citation-th">Estilo de Cita</th>
                            </tr>';

        foreach ($arrayData as $xrefId => $data) {
            $numRows = count($data['references']);
            $firstRow = true;
            
            foreach ($data['references'] as $referenceData) {
                $tableHTML .= "<tr class='citation-row'>";
                
                if ($firstRow) {
                    $tableHTML .= '<td rowspan="' . $numRows . '" class="citation-td">' . $data['context'] . '</td>';
                }

                $tableHTML .= "<td class='citation-td'>" . $referenceData['reference'] . "</td>";
                
                if ($firstRow) {
                    $citationOptions = [];
                    $years = [];
                    foreach ($data['references'] as $ref) {
                        $authors = $ref['authors'];
                        $year = $authors['data_1']['year'];
                        $years[] = $year;
                        $authorCount = count($authors);
                        
                        if ($authorCount == 1) {
                            $citationOptions[] = $authors['data_1']['surname'] . ', ' . $year;
                        } elseif ($authorCount == 2) {
                            $citationOptions[] = $authors['data_1']['surname'] . ' y ' . $authors['data_2']['surname'] . ', ' . $year;
                        } else {
                            $citationOptions[] = $authors['data_1']['surname'] . ' et al, ' . $year;
                        }
                    }
                    $citationText = implode('; ', $citationOptions);
                    $yearsText = implode('; ', array_unique($years));
                    
                    // Determinar qué opción mostrar como seleccionada
                    $isDefault = $data['status'] === 'default';
                    $customValue = isset($data['citationText']) ? $data['citationText'] : '';
                    $isCustom = !$isDefault && $customValue && $customValue !== "($citationText)" && $customValue !== "($yearsText)";
                    
                    // Construir las opciones para el elemento select
                    // Si es default, seleccionar la opción de citationText en lugar de la opción vacía
                    $citationTextOption = "<option value='($citationText)' " . ($isDefault || (!$isDefault && $customValue === "($citationText)") ? "selected" : "") . ">($citationText)</option>";
                    $yearsTextOption = "<option value='($yearsText)' " . (!$isDefault && $customValue === "($yearsText)" ? "selected" : "") . ">($yearsText)</option>";
                    $customOption = "<option value='custom' " . ($isCustom ? "selected" : "") . ">Otro</option>";

                    $tableHTML .= "<td rowspan='" . $numRows . "' class='citation-td'>
                                    <select name='citationStyle[{$xrefId}]' id='citationStyle_{$xrefId}' 
                                        class='citation-select'
                                        onchange='
                                            let inputField = document.getElementById(\"customInput_{$xrefId}\");
                                            if(this.value == \"custom\"){
                                                if(!inputField){
                                                    inputField = document.createElement(\"input\");
                                                    inputField.type = \"text\";
                                                    inputField.name = \"customCitation[{$xrefId}]\";
                                                    inputField.id = \"customInput_{$xrefId}\";
                                                    inputField.placeholder = \"ej: (González, 2011, p. 34)\";
                                                    inputField.className = \"custom-input\";
                                                    this.parentNode.appendChild(inputField);
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
                    
                    // Si es una opción personalizada, mostrar el campo de entrada con el valor
                    if ($isCustom) {
                        $tableHTML .= "<input type='text' name='customCitation[{$xrefId}]' id='customInput_{$xrefId}' 
                                      value='" . htmlspecialchars($customValue) . "' 
                                      placeholder='ej: (González, 2011, p. 34)' class='custom-input'>";
                    }
                    
                    $tableHTML .= "</td>";
                    $firstRow = false;
                }
            }
        }
        
        $tableHTML .= '</tr>
                        </table>
                            <button type="submit" class="save-btn-citations">
                                Guardar citas
                            </button>
                        </form>
                        </div>';

        $tableHTML .= self::getStyles();
        
        return $tableHTML;
    }

    //GET STYLES FOR THE HTML
    public static function getStyles() {
        return '<style>
                        .citation-form-container .save-btn-citations {
                            margin-top: 10px;
                            padding: 8px 12px;
                            background-color: #004e92;
                            color: white;
                            border: none;
                            cursor: pointer;
                            transition: transform 0.3s ease, background-color 0.3s ease, color 0.3s ease;
                            border-radius: 5px;
                            animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
                        }

                        .citation-form-container .save-btn-citations:hover {
                            transform: scale(1.08); 
                            background-color: #0073e6;
                            color: #000;
                        }

                        .citation-form-container .citation-error-message {
                            background-color: #ffebee;
                            color: #c62828;
                            padding: 10px;
                            margin-bottom: 15px;
                            border-left: 5px solid #c62828;
                            border-radius: 3px;
                            animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
                            font-weight: 500;
                        }
                        
                        .citation-form-container .error-icon {
                            margin-right: 6px;
                            font-size: 18px;
                        }
                        
                        .citation-form-container .citation-select-error {
                            border: 2px solid #c62828 !important;
                            background-color: #ffebee;
                        }

                        .citation-form-container .citation-table {
                            border: 1px solid #ddd;
                            width: 100%;
                            border-collapse: collapse;
                            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                            animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
                        }

                        .citation-form-container .citation-th {
                            background-color: #f4f4f4;
                            padding: 12px;
                            border: 1px solid #ddd;
                            text-align: left;
                            animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
                        }

                        .citation-form-container .citation-td {
                            padding: 12px;
                            border: 1px solid #ddd;
                            text-align: left;
                            animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
                        }

                        .citation-form-container .citation-select {
                            width: 100%;
                            padding: 8px;
                            min-width: 200px;
                            border: 1px solid #ccc;
                            border-radius: 4px;
                            animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
                        }

                        .citation-form-container .custom-input {
                            display: block;
                            margin-top: 10px;
                            width: 100%;
                            padding: 8px;
                            min-width: 200px;
                            border: 1px solid #ccc;
                            border-radius: 4px;
                            animation: fadeIn '. self::DELAY_TIME .'s ease-in-out;
                        }

                        @keyframes fadeIn {
                            from {
                                opacity: 0;
                            }
                            to {
                                opacity: 1;
                            }
                        }
                    </style>';
    }

}