<?php namespace PKP\components\forms\CitationStyles\Core\Formatters;

/**
 * Abstract class that defines the contract for citation formatters
 * This class provides a template for implementing different citation style formatters,
 * ensuring that all concrete implementations will have the necessary methods.
 */
abstract class AbstractCitationFormatter {
    /**
     * Format a citation with a single author
     * 
     * @param array $author The author data
     * @param mixed $year The publication year
     * @return string The formatted citation string
     */
    abstract public function formatSingleAuthorCitation(array $author, $year): string;
    
    /**
     * Format a citation with two authors
     * 
     * @param array $author1 The first author data
     * @param array $author2 The second author data
     * @param mixed $year The publication year
     * @return string The formatted citation string
     */
    abstract public function formatTwoAuthorsCitation(array $author1, array $author2, $year): string;
    
    /**
     * Format a citation with multiple authors
     * 
     * @param array $authors Array of author data
     * @param mixed $year The publication year
     * @return string The formatted citation string
     */
    abstract public function formatMultipleAuthorsCitation(array $authors, $year): string;
    
    /**
     * Get the separator to use between multiple citations
     * 
     * @return string The separator string
     */
    abstract public function getCitationSeparator(): string;
}