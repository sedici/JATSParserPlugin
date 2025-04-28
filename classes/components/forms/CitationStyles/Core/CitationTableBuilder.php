<?php namespace PKP\components\forms\CitationStyles\Core;

require_once __DIR__ . '/Renderers/ApaTableRenderer.php';
require_once __DIR__ . '/Stylesheets/ApaStylesheet.php';
require_once __DIR__ . '/Elements/Messages.php';
require_once __DIR__ . '/Elements/Buttons.php';

use PKP\components\forms\CitationStyles\Core\Renderers\ApaTableRenderer;
use PKP\components\forms\CitationStyles\Core\Stylesheets\ApaStylesheet;
use PKP\components\forms\CitationStyles\Core\Elements\Messages;
use PKP\components\forms\CitationStyles\Core\Elements\Buttons;

class CitationTableBuilder {
    private $formatter;
    private $data;
    private $xmlPath;
    private $citationStyle;
    private $publicationId;
    private $localeKey;

    public function __construct(
        $formatter,
        array $data,
        string $xmlPath,
        string $citationStyle,
        int $publicationId,
        string $localeKey
    ) {
        $this->formatter = $formatter;
        $this->data = $data;
        $this->xmlPath = $xmlPath;
        $this->citationStyle = $citationStyle;
        $this->publicationId = $publicationId;
        $this->localeKey = $localeKey;
    }
    

    public function build(): string {
        if (empty($this->data)) {
            return Messages::getEmptyCitationsMessage();
        }

        $html = '<div class="citation-form-container">';
        $html .= Messages::getErrorMessageHtml();
        
        $tableRendererClassname = 'PKP\\components\\forms\\CitationStyles\\Core\\Renderers\\' . ucfirst($this->citationStyle) . 'TableRenderer';
        $stylesheetClassname = 'PKP\\components\\forms\\CitationStyles\\Core\\Stylesheets\\' . ucfirst($this->citationStyle) . 'Stylesheet';
        
        $tableRenderer = new $tableRendererClassname($this->formatter, $this->xmlPath, $this->citationStyle, $this->publicationId, $this->localeKey);
        
        $html .= $tableRenderer->getFormOpeningHtml();
        $html .= $tableRenderer->getTableHeader();
        
        foreach ($this->data as $xrefId => $rowData) {
            $html .= $tableRenderer->renderCitationRow($xrefId, $rowData);
        }
        
        $html .= Buttons::getFormSaveButton();
        
        $html .= $stylesheetClassname::getStyles();
        
        return $html;
    }
}