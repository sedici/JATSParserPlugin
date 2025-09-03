<?php namespace PKP\components\forms\CitationStyles\Core\Formatters;


use PKP\components\forms\CitationStyles\Core\Formatters\AbstractCitationFormatter;

class ApaFormatter extends AbstractCitationFormatter {
    public function formatSingleAuthorCitation(array $author, $year): string {
        return $author['surname'] . ', ' . $year;
    }
    
    public function formatTwoAuthorsCitation(array $author1, array $author2, $year): string {
        return $author1['surname'] . ' ' . __('plugins.generic.jatsParser.citationtable.citationstyle.twoauthors.separator') . ' ' . $author2['surname'] . ', ' . $year;
    }
    
    public function formatMultipleAuthorsCitation(array $authors, $year): string {
        return $authors['data_1']['surname'] . ' et al, ' . $year;
    }
    
    public function getCitationSeparator(): string {
        return '; ';
    }

}