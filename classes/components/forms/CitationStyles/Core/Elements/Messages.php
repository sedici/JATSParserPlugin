<?php namespace PKP\components\forms\CitationStyles\Core\Elements;

class Messages {
    public static function getEmptyCitationsMessage(): string {
        return "<div><span>" . __('plugins.generic.citationtable.citations.notfound') . "</span></div>";
    }    

    public static function getErrorMessageHtml(): string {
        return '<div id="citationErrorMessage" class="citation-error-message" style="display:none;">
                <span class="error-icon">⚠️</span> ' . __('plugins.generic.jatsParser.citationtable.error.emptyoption') . '.
                </div>';
    }
}