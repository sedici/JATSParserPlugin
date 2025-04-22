<?php namespace PKP\components\forms\CitationStyles;

require_once __DIR__ . ('/GenericCitationTable.php');

class ApaCitationTable extends GenericCitationTable{

    protected function formatSingleAuthorCitation(array $author, string $year): string {
        return $author['surname'] . ', ' . $year;
    }

    protected function formatTwoAuthorsCitation(array $author1, array $author2, string $year): string {
        return $author1['surname'] . ' ' . __('plugins.generic.jatsParser.citationtable.citationstyle.twoauthors.separator') . ' ' . $author2['surname'] . ', ' . $year;
    }

    protected function formatMultipleAuthorsCitation(array $authors, string $year): string {
        return $authors['data_1']['surname'] . ' et al, ' . $year;
    }
    
    protected function getCitationSeparator(): string {
        return '; ';
    }
}