<?php

    function loadCitationTableConfig(int $publicationId) {
        $publicationService = Services::get('publication');
        $publication = $publicationService->get($publicationId); // Get publication using publicationId

        $citationJsonData = $publication->getData('jatsParser::citationTableData');
        $arrayData = json_decode($citationJsonData, true);

        return $arrayData;
    }