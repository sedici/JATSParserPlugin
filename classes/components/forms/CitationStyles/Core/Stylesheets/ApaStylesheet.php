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
                padding: 20px; /* This padding is for the modal content area itself */
                border: 1px solid #888;
                width: 100%; 
                max-width: 1400px; 
                border-radius: 5px;
            }
            
            .citation-modal-close {
                position: absolute;
                top: 5px; /* Adjusted from 10px to move it higher */
                right: 10px; /* Adjusted from 15px for a bit more space from edge if needed */
                color: #aaa;
                font-size: 24px;
                font-weight: bold;
                cursor: pointer;
            }

            /* Container for the scrollable table and form */
            .citation-form-container {
                padding-right: 20px; /* Adds space to the right, pushing content left from scrollbar */
                /* max-height and overflow are set inline in CitationTableBuilder.php */
            }

            /* SAVE BUTTON */
            .citation-form-container .save-btn-citations {
                margin-top: 10px;
                padding: 8px 12px;
                background-color: #0168d3;
                color: white;
                border: none;
                cursor: pointer;
                transition: transform 0.3s ease, background-color 0.3s ease, color 0.3s ease;
                border-radius: 5px;
            }

            .citation-form-container .save-btn-citations:hover {
                transform: scale(0.95); 
                background-color: #00b0df;
            }

            /*  TABLE STYLES */
            .citation-form-container .citation-table { /* Changed selector for higher specificity */
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse; /* Ensures borders combine nicely */
                border: 2px solid #000000; /* Sets the outer border for the table */
            }

            .citation-form-container table th,
            .citation-form-container table td {
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                white-space: normal !important; /* Make sure text wraps */
                border: 1px solid #ddd; /* Default light border for top, left, right */
                border-bottom: 1px solid #ddd; /* Default light border for bottom */
                padding: 8px; /* Default padding for all cells */
                text-align: left; /* Default text alignment */
            }

            .citation-form-container table th {
                background-color: #f8f8f8; /* Light background for header cells */
                font-weight: bold; /* Make header text bold */
            }

            /* Apply thick bottom border to all cells in the last row of a group */
            .citation-row.citation-group-last-row > td {
                border-bottom: 3px solid #000000; 
            }

            /* 
             * Apply thick bottom border to Context and Style Options cells.
             * These cells are in the first row of their group and use rowspan.
             * Their own bottom border must be thick to ensure a continuous line.
             */
            .citation-row > td[rowspan]:first-child, /* Context cell (first td with rowspan in a citation row) */
            .citation-row > td[rowspan].select-wrapper-cell /* Style Options cell (td with rowspan and select-wrapper-cell class) */
            {
                border-bottom: 3px solid #000000;
            }

            </style>';
    }
}