<?php namespace PKP\components\forms\CitationStyles\Core\Renderers;

class ApaFigsTablesRenderer {
    // Escape helper
    private function esc(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

    /**
     * Returns the opening HTML table tags with table headers
     * 
     * @return string HTML string containing table opening and headers
     */
    public function getTableHeader(): string {
        return '<table class="citation-table">
                <tr class="citation-header">
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.titlecontext') . '</th>
                    <th class="citation-th">' . __('plugins.generic.jatsParser.citationtable.figandtables.titles') . '</th>
                    <th class="citation-th"> ' . __('plugins.generic.jatsParser.citationtable.titlecitationstyle') . ' </th>
                </tr>';
    }

    /**
     * Main renderer (similar to ApaTableRenderer::renderCitationRow) for figures/tables citations.
     */
    public function renderCitationRow(string $xrefId, array $data): string {
        $context     = $data['context'] ?? '';
        $label       = trim((string)($data['originalText'] ?? ''));        // Eg: "Table 1-2" / "Figures 1-3"
        $singleTitle = trim((string)($data['title'] ?? ''));                // Single title if only one element
        $status      = $data['status'] ?? 'default';
        $customValue = trim((string)($data['citationText'] ?? ''));

        // Process every specific title of the citation (figures or tables)
        $rowsSource = [];
        if (!empty($data['titles']) && is_array($data['titles'])) {
            foreach ($data['titles'] as $title) { 
                $rowsSource[] = ['title' => $title['title'] ?? (is_string($title) ? $title : '')]; 
            }
        } 

        if (empty($rowsSource)) {
            $rowsSource = [['title' => ($singleTitle !== '' ? $singleTitle : $label)]];
        }

        // Selection logic
        $selectedValue = $label !== '' ? $label : $singleTitle;
        $isCustom = false;
        if ($status !== 'default' && $customValue !== '') {
            if ($customValue === $label) { $selectedValue = $label; }
            elseif ($singleTitle !== '' && $customValue === $singleTitle) { $selectedValue = $singleTitle; }
            else { $selectedValue = 'custom'; $isCustom = true; }
        }

        $numRows = count($rowsSource);
        $lastIndex = $numRows -1;
        $html=''; $first = true;
        foreach ($rowsSource as $i => $row) {
            $rowClass = 'citation-row' . ($i === $lastIndex ? ' citation-group-last-row' : '');
            $html .= "<tr class='{$rowClass}'>";
            if ($first) { $html .= '<td rowspan="' . $numRows . '" class="citation-td">' . $context . '</td>'; }
            $titleText = $row['title'] ?? '';
            $html .= "<td class='citation-td'>" . $this->esc($titleText) . "</td>";
            if ($first) { $html .= $this->buildSelectCell($numRows, $xrefId, $label, $singleTitle, $selectedValue, $status==='default', $isCustom, $customValue); $first=false; }
            $html .= '</tr>';
        }
        return $html;
    }

    /** Compatibility wrapper */
    public function renderRow(string $xrefId, array $data): string { return $this->renderCitationRow($xrefId, $data); }

    /**
     * Builds the <td> cell containing the <select> and (if applies) the custom input, using rowspan.
     */
    private function buildSelectCell(int $numRows, string $xrefId, string $label, string $singleTitle, string $selectedValue, bool $isDefault, bool $isCustom, string $customValue): string {
        $options = '';
        if ($label !== '') {
            $options .= '<option value="' . $this->esc($label) . '"' . ($selectedValue === $label ? ' selected' : '') . '>' . $this->esc($label) . '</option>';
        }
        if ($singleTitle !== '' && $singleTitle !== $label) {
            $options .= '<option value="' . $this->esc($singleTitle) . '"' . ($selectedValue === $singleTitle ? ' selected' : '') . '>' . $this->esc($singleTitle) . '</option>';
        }
        $customLabel = __('plugins.generic.jatsParser.citationtable.customtext');
        $options .= '<option value="custom"' . ($selectedValue === 'custom' ? ' selected' : '') . '>' . $this->esc($customLabel) . '</option>';

        // data-original-value: baseline value considered as "unchanged"
        if ($selectedValue === 'custom') {
            $originalValue = 'custom';
        } elseif ($selectedValue === $singleTitle && $singleTitle !== '' && !$isCustom && !$isDefault) {
            $originalValue = $singleTitle;
        } else {
            $originalValue = ($label !== '' ? $label : $singleTitle);
        }

        $td  = "<td rowspan='" . (int)$numRows . "' class='citation-td select-wrapper-cell'>";
        $td .= "<select name='citationStyle[" . $this->esc($xrefId) . "]' id='citationStyle_" . $this->esc($xrefId) . "' class='citation-select citation-original' data-original-value='" . $this->esc($originalValue) . "'>" . $options . '</select>';
        if ($selectedValue === 'custom') {
            $td .= "<input type='text' name='customCitation[" . $this->esc($xrefId) . "]' id='customInput_" . $this->esc($xrefId) . "' value='" . $this->esc($customValue) . "' placeholder='e.g.: (González, 2011, p. 34)' class='custom-input citation-original' data-original-value='" . $this->esc($customValue) . "'>";
        }
        $td .= '</td>';
        return $td;
    }
}
