<?php namespace PKP\components\forms\CitationStyles\Core\Elements;

/**
 * Class responsible for generating various message elements used in citation tables
 * This class provides static methods to create standardized messages for different
 * scenarios related to the citation table.
 */
class Messages {
    /**
     * Returns an HTML message to display when no citations are found
     * 
     * @return string HTML string containing the empty citations message
     */
    public static function getEmptyCitationsMessage(): string {
        return "<div><span>" . __('plugins.generic.citationtable.citations.notfound') . "</span></div>";
    }    

    /**
     * Returns an HTML error message for when a user selects custom citation option
     * but doesn't enter any text
     * 
     * @return string HTML string containing the error message (initially hidden)
     */
    public static function getErrorMessageHtml(): string {
        return '<div id="citationErrorMessage" class="citation-error-message" style="display:none;">
                <span class="error-icon">⚠️</span> ' . __('plugins.generic.jatsParser.citationtable.error.emptyoption') . '.
                </div>';
    }
}