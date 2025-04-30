<?php namespace PKP\components\forms\CitationStyles\Core\Elements;

    class Modal {
        public static function getOpeningCitationModal() {
            return '<div id="citationModal" class="citation-modal" style="display: none;" 
                    onclick="if(event.target == this) { this.style.display=\'none\'; }">
                    <div class="citation-modal-content">
                    <span class="citation-modal-close" 
                    onclick="document.getElementById(\'citationModal\').style.display=\'none\';">&times;</span>';
        }

        public static function getClosingCitationModal(){
            return '</div></div></div>';
        }
    }