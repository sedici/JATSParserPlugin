<?php namespace PKP\components\forms\CitationStyles\Core\Elements;

/**
 * Class that provides HTML button elements for citation style forms
 * These buttons are used in CitationTableBuilder class
 */
class Buttons {

    /**
     * Generates the HTML for the form's save button
     * This button is used to submit the citations styles selected in the form
     * 
     * @return string HTML markup for the save button
     */
    public static function getFormSaveButton() {
        return '<button type="submit" class="save-btn-citations">
                    ' . __('plugins.generic.jatsParser.citationtable.savebuttontext') . '
                </button>';
    }

    /**
     * Generates the HTML for the button that opens the citations modal
     * This button triggers the display of a modal containing the citation table with 
     * all the citations information
     * 
     * @return string HTML markup for the view citations button
     */
    public static function getViewCitationsButton(): string {
        return '<button type="button" id="openCitationModalBtn" 
                onclick="document.getElementById(\'citationModal\').style.display=\'block\';" 
                class="view-btn-citations">
                ' . __('plugins.generic.jatsParser.citationtable.viewcitations') . '
                </button>';
    }
}