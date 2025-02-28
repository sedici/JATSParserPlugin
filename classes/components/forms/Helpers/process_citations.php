<?php

require_once __DIR__ . '/../Helpers/getPublicationId.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['citationStyleName'])) {

        $xmlFilePath = $_POST['xmlFilePath'];

        $unifiedArray = [];
        foreach ($_POST['citationStyle'] as $rid => $citationStyle) {
            $unifiedArray[$rid] = $citationStyle;
        }
        foreach ($_POST['customCitation'] as $rid => $customCitation) {
            $unifiedArray[$rid] = $customCitation;
        }
        $unifiedArray['citationStyleName'] = $_POST['citationStyleName'];
        $unifiedArray['xmlFilePath'] = $xmlFilePath;
    
        $dom = new DOMDocument;
        $dom->load($xmlFilePath);
        $xpath = new DOMXPath($dom);

        $xrefs = $xpath->query('//article/body/sec//xref');
        foreach ($unifiedArray as $unique_id => $citationText) {
            foreach ($xrefs as $xref) {
                if ($xref->getAttribute('id') === $unique_id) {
                    $xref->nodeValue = $citationText;
                }
            }
        }

        $dom->save($xmlFilePath);

        //GET PUBLICATION URL
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $publicationId = getPublicationId($path);

        if ($publicationId) {
            $publicationDao = DAORegistry::getDAO('PublicationDAO');
            $publicationService = Services::get('publication');
            $publication = $publicationService->get($publicationId); // Get publication using publicationId
            
            $citationJsonData = json_encode($unifiedArray);
            $publication->setData('jatsParser::citationTableData', $citationJsonData); // Save JSON data to publication
            $json = $publication->getData('jatsParser::citationTableData');
            $publicationDao->updateObject($publication);

            print_r($unifiedArray);

        }
    
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
} 

?>