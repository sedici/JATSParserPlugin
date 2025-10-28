<?php namespace PKP\components\forms\CitationStyles\Core\Renderers;

class ApaTableRenderer {
    /**
     * Absolute path to the XML file containing citation data
     * @var string
     */
    public $absoluteXmlPath;

    /**
     * The selected citation style being used
     * @var string
     */
    public $citationStyle;

    /**
     * The ID of the publication that has the citations
     * @var int
     */
    public $publicationId;

    /**
     * The locale key used for internationalization
     * @var string
     */
    public $localeKey;

    /**
     * Constructor for the APA Table Renderer
     * 
     * @param string $absoluteXmlPath Path to the XML file with citation data
     * @param string $citationStyle The citation style to use
     * @param int $publicationId The publication ID
     * @param string $localeKey The locale key for translation
     */
    public function __construct(string $absoluteXmlPath, string $citationStyle, int $publicationId, string $localeKey) {
        $this->absoluteXmlPath = $absoluteXmlPath;
        $this->citationStyle = $citationStyle;
        $this->publicationId = $publicationId;
        $this->localeKey = $localeKey;
    }

    /**
     * Returns the opening HTML form tags with necessary hidden fields.
     */
    public function getFormOpening(string $formId = 'citationForm'): string {
        return "<form method=\"POST\" target=\"_self\" id=\"" . htmlspecialchars($formId) . "\" class=\"citation-form\">"
            . "<input type=\"hidden\" name=\"xmlFilePath\" value=\"" . htmlspecialchars($this->absoluteXmlPath) . "\">"
            . "<input type=\"hidden\" name=\"citationStyleName\" value=\"" . htmlspecialchars($this->citationStyle) . "\">"
            . "<input type=\"hidden\" name=\"publicationId\" value=\"" . htmlspecialchars($this->publicationId) . "\">"
            . "<input type=\"hidden\" name=\"locale_key\" value=\"" . htmlspecialchars($this->localeKey) . "\">";
    }

    /**
     * Returns the closing HTML form tag.
     */
    public function getClosingForm(): string {
        return '</form>';
    }

    /**
     * Returns the closing HTML table tag.
     */
    public function getClosingTable(): string {
        return '</table>';
    }

    /**
     * Renders the citation tabs (References / Figures & Tables).
     */
    public function getCitationTabs(): string {
        return '<div class="citation-tabs" role="tablist" aria-orientation="horizontal">'
               . '<button type="button" class="citation-tab-button active" data-target="#citation-tab-references">' . __('plugins.generic.jatsParser.citationtable.titlereferences') . '</button>'
               . '<button type="button" class="citation-tab-button" data-target="#citation-tab-figtables">' . __('plugins.generic.jatsParser.citationtable.titlefigsandtables') . '</button>'
               . '</div>';
    }
}