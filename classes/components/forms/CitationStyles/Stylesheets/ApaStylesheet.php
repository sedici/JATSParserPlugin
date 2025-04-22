<?php namespace PKP\components\forms\CitationStyles\Stylesheets;

require_once __DIR__ . ('/GenericStylesheet.php');

class ApaStylesheet extends GenericStylesheet {
    
    // AbstractStylesheet constants could be overridden here if needed
    // private const DELAY_TIME = 0.9; // Example of overriding a constant
    private const HIGHLIGHT_COLOR = '#f0f8ff';
    private const LINK_COLOR = '#0066cc';
    private const HOVER_HIGHLIGHT = '#e1f0ff';

    //Specific styles for APA citation
    public static function getStyles(): string {
        return '<style>
            ' . self::getCommonStyles() . '

            .citation-text {
                color: ' . self::LINK_COLOR . ';
                font-weight: bold;
                background-color: ' . self::HIGHLIGHT_COLOR . ';
                padding: 0 3px;
                border-radius: 3px;
                transition: background-color 0.2s;
            }
            
            .citation-text:hover {
                background-color: ' . self::HOVER_HIGHLIGHT . ';
            }
            
        </style>';
    }
}