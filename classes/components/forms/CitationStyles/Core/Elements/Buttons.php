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
    public static function getViewCitationsButton(string $localeKey = ''): string {
        $localeAttr = $localeKey ? ' data-locale="' . htmlspecialchars($localeKey) . '"' : '';
        $idAttr = $localeKey ? ' id="openCitationModalBtn_' . htmlspecialchars($localeKey) . '"' : ' id="openCitationModalBtn"';
        return '<button type="button"' . $idAttr . ' class="pkpButton view-btn-citations"' . $localeAttr . '>'
            . '<span class="fa fa-list-alt" aria-hidden="true"></span> '
            . __('plugins.generic.jatsParser.citationtable.viewcitations') .
            '</button>';
    }
}