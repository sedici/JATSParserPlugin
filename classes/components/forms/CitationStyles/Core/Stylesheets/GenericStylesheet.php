<?php namespace PKP\components\forms\CitationStyles\Core\Stylesheets;

abstract class GenericStylesheet {
    // Common constants for all stylesheets
    protected const DELAY_TIME = 0.5;
    protected const DEFAULT_TEXT_COLOR = '#333333';
    protected const ERROR_COLOR = '#c62828';
    protected const SUCCESS_COLOR = '#4CAF50';
    protected const WARNING_COLOR = '#FFC107';
    
    //Common method that all citation stylesheets must implement   
    abstract public static function getStyles(): string;
    
    // Method to get the common styles 
    protected static function getCommonStyles(): string {
        return '
            /* BASIC STRUCTURE */
            .citation-form-container .citation-table {
                border: 1px solid #ddd;
                width: 100%;
                border-collapse: collapse;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }

            .citation-form-container .citation-th {
                background-color: #f4f4f4;
                padding: 12px;
                border: 1px solid #ddd;
                text-align: left;
            }

            .citation-form-container .citation-td {
                padding: 12px;
                border: 1px solid #ddd;
                text-align: left;
            }
            
            /* VIEW CITATIONS BUTTON */
             .view-btn-citations {
                margin-top: 10px;
                padding: 8px 12px;
                background-color: #2196F3;
                color: white;
                border: none;
                cursor: pointer;
                transition: transform 0.3s ease, background-color 0.3s ease;
                border-radius: 5px;
                margin-right: 10px;
            }

            .view-btn-citations:hover {
                transform: scale(1.08);
                background-color: #0b7dda;
            }
            
            /* ERROR MESSAGES */
            .citation-form-container .citation-error-message {
                background-color: #ffebee;
                color: ' . self::ERROR_COLOR . ';
                padding: 10px;
                margin-bottom: 15px;
                border-left: 5px solid ' . self::ERROR_COLOR . ';
                border-radius: 3px;
                font-weight: 500;
            }
            
            .citation-form-container .error-icon {
                margin-right: 6px;
                font-size: 18px;
            }
            
            /* STATUS INDICATOR */
            .citation-form-container .citation-select-error {
                border: 2px solid ' . self::ERROR_COLOR . ' !important;
                background-color: #ffebee;
            }

            .citation-form-container .citation-original {
                border: 2px solid ' . self::SUCCESS_COLOR . ' !important;
                background-color: rgba(76, 175, 80, 0.05);
                transition: all 0.3s ease;
            }

            .citation-form-container .citation-modified {
                border: 2px solid ' . self::WARNING_COLOR . ' !important;
                background-color: rgba(255, 193, 7, 0.05);
                transition: all 0.3s ease;
            }
            
            /* FORM CONTROL */
            .citation-form-container .citation-select {
                width: 100%;
                padding: 8px;
                min-width: 200px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            .citation-form-container .custom-input {
                display: block;
                margin-top: 10px;
                width: 100%;
                padding: 8px;
                min-width: 200px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            .citation-form-container .select-wrapper-cell select {
                margin: 0 auto;
                display: block;
            }
                
            
            /* ANIMATIONS */
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        ';
    }
}