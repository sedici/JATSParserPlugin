<?php namespace PKP\components\forms\CitationStyles\Core\Formatters;

abstract class AbstractCitationFormatter {
    abstract public function formatSingleAuthorCitation(array $author, $year): string;
    abstract public function formatTwoAuthorsCitation(array $author1, array $author2, $year): string;
    abstract public function formatMultipleAuthorsCitation(array $authors, $year): string;
    abstract public function getCitationSeparator(): string;
}