<?php

require_once __DIR__ . '/../Helpers/getPublicationId.php';
require_once __DIR__ . '/../../../daos/CustomPublicationSettingsDAO.inc.php';

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
            $citationJsonData = json_encode($unifiedArray);

            // Usar la clase DAO personalizada para actualizar el campo
            $customPublicationSettingsDao = new CustomPublicationSettingsDAO();
            $customPublicationSettingsDao->updateSetting($publicationId, 'jatsParser::citationTableData', $citationJsonData);
            
            // Obtener los datos actualizados
            $data = $customPublicationSettingsDao->getSetting($publicationId, 'jatsParser::citationTableData');
            print_r($data);
        }
    
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
} 

?>