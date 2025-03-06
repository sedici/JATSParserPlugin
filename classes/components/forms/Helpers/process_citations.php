<?php

require_once __DIR__ . '/../Helpers/getPublicationId.php';
require_once __DIR__ . '/../../../daos/CustomPublicationSettingsDAO.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['citationStyleName'])) {

        $unifiedArray = [];
        foreach ($_POST['citationStyle'] as $rid => $citationStyle) {
            $unifiedArray[$rid] = $citationStyle;
        }
        foreach ($_POST['customCitation'] as $rid => $customCitation) {
            $unifiedArray[$rid] = $customCitation;
        }
        $unifiedArray['citationStyleName'] = $_POST['citationStyleName'];
        $unifiedArray['xmlFilePath'] = $_POST['xmlFilePath'];
        $unifiedArray['publicationId'] = $_POST['publicationId'];
        
        if ($_POST['publicationId']) {
            $citationJsonData = json_encode($unifiedArray);

            // Using DAO to update the setting
            $customPublicationSettingsDao = new CustomPublicationSettingsDAO();
            $customPublicationSettingsDao->updateSetting($_POST['publicationId'], 'jatsParser::citationTableData', $citationJsonData);
        }
    
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
} 

?>