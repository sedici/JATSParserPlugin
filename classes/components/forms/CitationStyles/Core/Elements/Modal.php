<?php namespace PKP\components\forms\CitationStyles\Core\Elements;

    class Modal {
        public static function getOpeningCitationModal() {
            return '<div id="citationModal" class="citation-modal" style="display: none;">
                    <div class="citation-modal-content">
                    <span class="citation-modal-close">&times;</span>';
        }

        public static function getClosingCitationModal(){
            return '</div></div></div>';
        }
    }