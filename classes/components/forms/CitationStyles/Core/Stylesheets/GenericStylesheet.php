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
                animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
            }

            .citation-form-container .citation-th {
                background-color: #f4f4f4;
                padding: 12px;
                border: 1px solid #ddd;
                text-align: left;
                animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
            }

            .citation-form-container .citation-td {
                padding: 12px;
                border: 1px solid #ddd;
                text-align: left;
                animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
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
                animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
            }

            .citation-form-container .save-btn-citations:hover {
                transform: scale(1.08); 
                background-color: #0073e6;
            }
            
            /* ERROR MESSAGES */
            .citation-form-container .citation-error-message {
                background-color: #ffebee;
                color: ' . self::ERROR_COLOR . ';
                padding: 10px;
                margin-bottom: 15px;
                border-left: 5px solid ' . self::ERROR_COLOR . ';
                border-radius: 3px;
                animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
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
                animation: fadeIn ' . self::DELAY_TIME . 's ease-in-out;
            }

            .citation-form-container .custom-input {
                display: block;
                margin-top: 10px;
                width: 100%;
                padding: 8px;
                min-width: 200px;
                border: 1px solid #ccc;
                border-radius: 4px;
                animation: fadeIn '. self::DELAY_TIME .'s ease-in-out;
            }
            
            /* ANIMATIONS */
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        ';
    }
}