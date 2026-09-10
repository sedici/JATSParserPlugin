<?php namespace PKP\components\forms\CitationStyles\Core;

require_once __DIR__ . '/Renderers/ApaTableRenderer.php';
require_once __DIR__ . '/Renderers/ApaReferencesRenderer.php';
require_once __DIR__ . '/Renderers/ApaFigsTablesRenderer.php';

require_once __DIR__ . '/Elements/Messages.php';
require_once __DIR__ . '/Elements/Buttons.php';
require_once __DIR__ . '/Elements/Modal.php';

use PKP\components\forms\CitationStyles\Core\Renderers\ApaTableRenderer;
use PKP\components\forms\CitationStyles\Core\Renderers\ApaFigsTablesRenderer;
use PKP\components\forms\CitationStyles\Core\Renderers\ApaReferencesRenderer;

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
    private $bibrCitationData;

    /* Citation data for figures and tables */
    private $figAndTableCitationData;

    /* Path to the XML file containing the publication data */
    private $xmlPath;
    
    /* The citation style to be used (e.g., 'apa') */
    private $citationStyle;
    
    /* ID of the publication being processed */
    private $publicationId;
    
    /* Locale key for internationalization */
    /* Human friendly name of the XML file */
    private $humanXmlFileName;

    /**
     * Constructor for the CitationTableBuilder
     * 
     * @param object $formatter The citation formatter object
     * @param array $data The citation data to be rendered
     * @param string $xmlPath Path to the XML file containing the publication data
     * @param string $citationStyle The citation style to be used (e.g., 'apa')
     * @param int $publicationId ID of the publication being processed
     * @param string $localeKey Locale key for internationalization
     * @param string|null $humanXmlFileName Human friendly XML name
     */
    public function __construct(
        $formatter,
        array $bibrCitationData,
        array $figAndTableCitationData,
        string $xmlPath,
        string $citationStyle,
        int $publicationId,
        string $localeKey,
        ?string $humanXmlFileName = null
    ) {
        $this->formatter = $formatter;
        $this->bibrCitationData = $bibrCitationData;
        $this->figAndTableCitationData = $figAndTableCitationData;
        $this->xmlPath = $xmlPath;
        $this->citationStyle = $citationStyle;
        $this->publicationId = $publicationId;
        $this->localeKey = $localeKey;
        $this->humanXmlFileName = $humanXmlFileName;
    }
    
    /** 
     * Builds the citation table HTML
     * 
     * @return string The HTML representation of the citation table
     */
    public function build(): string {
        // If there are no citations of any type, show empty message
        if (empty($this->bibrCitationData) && empty($this->figAndTableCitationData)) {
            return Messages::getEmptyCitationsMessage();
        }

        $html = Buttons::getViewCitationsButton();
        $html .= Modal::getOpeningCitationModal();

        $html .= '<div class="citation-form-container" style="max-height: 80vh; overflow-y: auto; overflow-x: hidden;">';
        $html .= Messages::getErrorMessageHtml();

        $displayXmlName = !empty($this->humanXmlFileName) ? $this->humanXmlFileName : basename($this->xmlPath);
        $html .= '<div class="citation-modal-xml-notice" style="background: #eef5fa; border: 1px solid #cce2f0; border-left: 4px solid #006798; border-radius: 4px; padding: 9px 14px; margin-bottom: 14px; font-size: 0.88rem; color: #006798; display: flex; align-items: center; gap: 8px;">'
            . '<span class="fa fa-file-code-o" style="font-size: 1.05rem;" aria-hidden="true"></span>'
            . '<span><strong>' . __('plugins.generic.jatsParser.publication.jats.citations.modalXmlNotice') . ':</strong> ' . htmlspecialchars($displayXmlName) . '</span>'
            . '</div>';

        // Instantiate table renderer, figures/tables renderer and references renderer
        $tableRendererClassname = 'PKP\\components\\forms\\CitationStyles\\Core\\Renderers\\' . ucfirst($this->citationStyle) . 'TableRenderer';
        $tableRenderer = new $tableRendererClassname($this->xmlPath, $this->citationStyle, $this->publicationId, $this->localeKey);
        
        $tableFigsRendererClassname = 'PKP\\components\\forms\\CitationStyles\\Core\\Renderers\\' . ucfirst($this->citationStyle) . 'FigsTablesRenderer';
        $tableFigsRenderer = new $tableFigsRendererClassname();

        $tableReferencesRendererClassname = 'PKP\\components\\forms\\CitationStyles\\Core\\Renderers\\' . ucfirst($this->citationStyle) . 'ReferencesRenderer';
        $tableReferencesRenderer = new $tableReferencesRendererClassname($this->formatter, $this->xmlPath, $this->citationStyle, $this->publicationId, $this->localeKey);

        $html .= $tableRenderer->getFormOpening('citationFormAll'); // Open single form for both tabs
        $html .= $tableRenderer->getCitationTabs(); // Tabs header
        $html .= '<div class="citation-tab-panels">'; // Panels wrapper

        // References panel
        $html .= '<div id="citation-tab-references" class="citation-tab-panel is-active">';
        if (!empty($this->bibrCitationData)) {
            $html .= $tableReferencesRenderer->getTableHeader();
            foreach ($this->bibrCitationData as $xrefId => $rowData) {
                $html .= $tableReferencesRenderer->renderCitationRow($xrefId, $rowData);
            }
            $html .= $tableRenderer->getClosingTable();

        } else {
            $html .= Messages::getEmptyCitationsMessage();
        }
        $html .= '</div>';

        // Figures and Tables panel
        $html .= '<div id="citation-tab-figtables" class="citation-tab-panel">';

        if (!empty($this->figAndTableCitationData)) {
            $html .= $tableFigsRenderer->getTableHeader();
            foreach ($this->figAndTableCitationData as $xrefId => $rowData) {
                $html .= $tableFigsRenderer->renderCitationRow($xrefId, $rowData);
            }
            $html .= $tableRenderer->getClosingTable();
        } else {
            $html .= Messages::getEmptyCitationsMessage();
        }
        $html .= '</div>'; // end figures/tables panel

        $html .= '</div>'; // end panels wrapper

        // Close button at bottom of modal
        $html .= '<div style="margin-top: 18px; display: flex; justify-content: flex-end; padding-top: 12px; border-top: 1px solid #e0e0e0;">'
            . '<button type="button" class="pkpButton citation-modal-close-btn" onclick="document.getElementById(\'citationModal\').style.display=\'none\';">'
            . __('common.close')
            . '</button>'
            . '</div>';
        $html .= $tableRenderer->getClosingForm();

        $html .= '</div>'; // citation-form-container
        $html .= Modal::getClosingCitationModal();

        return $html;
    }
}