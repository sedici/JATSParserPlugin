<?php namespace PKP\components\forms\CitationStyles;

class ApaStyle{

/* Example of expected array structure:
$arrayData = [
    'ref_0' => [
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
    'ref_1' => [
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

    public static function makeHtml(array $arrayData){
        $tableHTML = '<form method="POST" action="process_cites.php" target="_self">
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
                                        <option value='apellidoAno'>($citationText)</option>
                                        <option value='ano'>($yearsText)</option>
                                        <option value='custom'>Otro</option>
                                    </select>
                                </td>";
                    $firstRow = false;
                }
                
                $tableHTML .= "</tr>";
            }
            
        }
        
        $tableHTML .= '</table>
                        <button type="submit" class="save-btn">
                            Guardar citas
                        </button>
                    </form>

                    <style>
                        .save-btn {
                            margin-top: 10px;
                            padding: 8px 12px;
                            background-color: #004e92;
                            color: white;
                            border: none;
                            cursor: pointer;
                            transition: transform 0.3s ease, background-color 0.3s ease, color 0.3s ease;
                            font-family: "Arial", sans-serif;
                            border-radius: 5px;
                        }

                        .save-btn:hover {
                            transform: scale(1.08); 
                            background-color: #0073e6;
                            color: #000;
                        }

                        .citation-table {
                            font-family: "Arial", sans-serif;
                            border: 1px solid #ddd;
                            width: 100%;
                            border-collapse: collapse;
                            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                        }

                        .citation-th {
                            background-color: #f4f4f4;
                            font-weight: bold;
                            padding: 12px;
                            border: 1px solid #ddd;
                            text-align: left;
                        }

                        .citation-td {
                            padding: 12px;
                            border: 1px solid #ddd;
                            text-align: left;
                        }

                        .citation-select {
                            width: 100%;
                            padding: 8px;
                            min-width: 200px;
                            border: 1px solid #ccc;
                            border-radius: 4px;
                            font-size: 14px;
                        }

                        .custom-input {
                            display: block;
                            margin-top: 10px;
                            width: 100%;
                            padding: 8px;
                            min-width: 200px;
                            border: 1px solid #ccc;
                            border-radius: 4px;
                            font-size: 14px;
                        }
                    </style>';
        
        return $tableHTML;
    }

}