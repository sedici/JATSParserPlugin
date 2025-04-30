<?php namespace PKP\components\forms\CitationStyles\Core\Stylesheets;

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

            /* Modal styles */
            .view-btn-citations {
                padding: 8px 15px;
                background-color: #0072CE; /* Color OJS */
                color: white;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                margin: 10px 0;
                font-size: 14px;
            }
            
            .citation-modal {
                display: none;
                position: fixed;
                z-index: 9999; /* Asegurarse que esté por encima de todo */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.5);
            }
            
            .citation-modal-content {
                position: relative;
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 800px;
                border-radius: 5px;
            }
            
            .citation-modal-close {
                position: absolute;
                top: 10px;
                right: 15px;
                color: #aaa;
                font-size: 24px;
                font-weight: bold;
                cursor: pointer;
            }

            /* SAVE BUTTON */
            .citation-form-container .save-btn-citations {
                margin-top: 10px;
                padding: 8px 12px;
                background-color: #004e92;
                color: white;
                border: none;
                cursor: pointer;
                transition: transform 0.3s ease, background-color 0.3s ease, color 0.3s ease;
                border-radius: 5px;
            }

            .citation-form-container .save-btn-citations:hover {
                transform: scale(1.08); 
                background-color: #0073e6;
            }

            </style>';
    }
}