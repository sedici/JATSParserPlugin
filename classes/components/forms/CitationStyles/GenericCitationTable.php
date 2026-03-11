<?php namespace PKP\components\forms\CitationStyles;

require_once __DIR__ . '/../Helpers/process_citations.php';
require_once __DIR__ . '/Core/CitationTableBuilder.php';
require_once __DIR__ . '/Core/Formatters/AbstractCitationFormatter.php';

use PKP\components\forms\CitationStyles\Core\CitationTableBuilder;

abstract class GenericCitationTable {
    protected $arrayData;
    protected $bibrCitationsData;
    protected $figAndTableCitationsData;

    protected $absoluteXmlPath;
    protected $citationStyle;
    protected $publicationId;
    protected $localeKey;
    protected $formatter;
    
    public function __construct(array $arrayData, string $absoluteXmlPath, string $citationStyle, int $publicationId, string $localeKey) {
        $this->arrayData = $arrayData;

        $this->bibrCitationsData = $arrayData['bibr_citations_data'] ?? [];
        $this->figAndTableCitationsData = $arrayData['figs_tables_citations_data'] ?? [];

        $this->absoluteXmlPath = $absoluteXmlPath;
        $this->citationStyle = $citationStyle;
        $this->publicationId = $publicationId;
        $this->localeKey = $localeKey;
        
        $this->initFormatter();
    }

    protected abstract function initFormatter(): void;

    public function makeHtml() {
        $builder = new CitationTableBuilder(
            $this->formatter,
            $this->bibrCitationsData, 
            $this->figAndTableCitationsData,
            $this->absoluteXmlPath, 
            $this->citationStyle, 
            $this->publicationId, 
            $this->localeKey
        );
        
        return $builder->build();
    }
}