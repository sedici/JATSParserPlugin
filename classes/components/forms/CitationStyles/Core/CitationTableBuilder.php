<?php namespace PKP\components\forms\CitationStyles\Core;

require_once __DIR__ . '/Renderers/ApaTableRenderer.php';
require_once __DIR__ . '/Stylesheets/ApaStylesheet.php';
require_once __DIR__ . '/Elements/Messages.php';
require_once __DIR__ . '/Elements/Buttons.php';
require_once __DIR__ . '/Elements/Modal.php';

use PKP\components\forms\CitationStyles\Core\Renderers\ApaTableRenderer;
use PKP\components\forms\CitationStyles\Core\Stylesheets\ApaStylesheet;
use PKP\components\forms\CitationStyles\Core\Elements\Messages;
use PKP\components\forms\CitationStyles\Core\Elements\Buttons;
use PKP\components\forms\CitationStyles\Core\Elements\Modal;

/* 
 * CitationTableBuilder
 * 
 * This class is responsible for building citation tables based on specified citation styles.
 * It handles the rendering of the citation data into HTML tables with proper formatting
 * according to the selected citation style (e.g., APA).
 */
class CitationTableBuilder {
    /* The formatter object used to format citation entries */
    private $formatter;
    
    /* Citation data organized as an array with citation references */
    private $data;
    
    /* Path to the XML file containing the publication data */
    private $xmlPath;
    
    /* The citation style to be used (e.g., 'apa') */
    private $citationStyle;
    
    /* ID of the publication being processed */
    private $publicationId;
    
    /* Locale key for internationalization */
    private $localeKey;

    /**
     * Constructor for the CitationTableBuilder
     * 
     * @param object $formatter The citation formatter object
     * @param array $data The citation data to be rendered
     * @param string $xmlPath Path to the XML file containing the publication data
     * @param string $citationStyle The citation style to be used (e.g., 'apa')
     * @param int $publicationId ID of the publication being processed
     * @param string $localeKey Locale key for internationalization
     */
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
    
    /** 
     * Builds the citation table HTML
     * 
     * @return string The HTML representation of the citation table
     */
    public function build(): string {
        if (empty($this->data)) {
            return Messages::getEmptyCitationsMessage();
        }

        $html = Buttons::getViewCitationsButton();
        $html .= Modal::getOpeningCitationModal();

        $html .= '<div class="citation-form-container" style="max-height: 80vh; overflow-y: auto; overflow-x: hidden;">';

        $html .= Messages::getErrorMessageHtml();

        $tableRendererClassname = 'PKP\\components\\forms\\CitationStyles\\Core\\Renderers\\' . ucfirst($this->citationStyle) . 'TableRenderer';

        $tableRenderer = new $tableRendererClassname($this->formatter, $this->xmlPath, $this->citationStyle, $this->publicationId, $this->localeKey);

        $html .= $tableRenderer->getFormOpening();
        $html .= $tableRenderer->getTableHeader();

        foreach ($this->data as $xrefId => $rowData) {
            $html .= $tableRenderer->renderCitationRow($xrefId, $rowData);
        }

        $html .= $tableRenderer->getClosingTable();

        $html .= Buttons::getFormSaveButton();

        $html .= $tableRenderer->getClosingForm();

        $html .= '</div>'; // Closing the citation-form-container div

        $html .= Modal::getClosingCitationModal();

        // Ya no agregues $stylesheetClassname::getStyles();
        // El CSS se cargará como archivo externo

        return $html;
    }
}