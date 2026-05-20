<?php namespace PKP\components\forms\CitationStyles;

require_once __DIR__ . ('/GenericCitationTable.php');
require_once __DIR__ . '/Core/Formatters/ApaFormatter.php';

use PKP\components\forms\CitationStyles\Core\Formatters\ApaFormatter;

class ApaCitationTable extends GenericCitationTable {
    protected function initFormatter(): void {
        $this->formatter = new ApaFormatter();
    }
}