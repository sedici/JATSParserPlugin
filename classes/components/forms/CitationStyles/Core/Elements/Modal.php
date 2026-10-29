<?php namespace PKP\components\forms\CitationStyles\Core\Elements;

    class Modal {
        public static function getOpeningCitationModal(string $localeKey = '') {
            $idAttr = $localeKey ? ' id="citationModal_' . htmlspecialchars($localeKey) . '"' : ' id="citationModal"';
            $localeAttr = $localeKey ? ' data-locale="' . htmlspecialchars($localeKey) . '"' : '';
            return '<div' . $idAttr . ' class="citation-modal"' . $localeAttr . ' style="display: none;">
                    <div class="citation-modal-content">
                    <span class="citation-modal-close" data-locale="' . htmlspecialchars($localeKey) . '">&times;</span>';
        }

        public static function getClosingCitationModal(){
            return '</div></div>';
        }
    }