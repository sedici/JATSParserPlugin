<?php namespace PKP\components\forms\CitationStyles\Core\Elements;

/**
 * Class that provides HTML button elements for citation style forms
 * These buttons are used in CitationTableBuilder class
 */
class Buttons {

    /**
     * Generates the HTML for the button that opens the citations modal
     * This button triggers the display of a modal containing the citation table with 
     * all the citations information
     * 
     * @return string HTML markup for the view citations button
     */
    public static function getViewCitationsButton(): string {
        return '<button type="button" id="openCitationModalBtn" class="pkpButton view-btn-citations">'
            . '<span class="fa fa-list-alt" aria-hidden="true"></span> '
            . __('plugins.generic.jatsParser.citationtable.viewcitations') .
            '</button>';
    }
}