<?php

require_once __DIR__ . '/../Helpers/getPublicationId.php';
require_once __DIR__ . '/../../../daos/CustomPublicationSettingsDAO.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['citationStyleName'])) {

        $unifiedArray = [];

        $unifiedArray['citationStyleName'] = $_POST['citationStyleName'];
        $unifiedArray['publicationId'] = $_POST['publicationId'];
        $unifiedArray['locale_key'] = $_POST['locale_key'];

        foreach ($_POST['citationStyle'] as $rid => $citationStyle) {
            $citationsArray[$rid] = $citationStyle;
        }
        foreach ($_POST['customCitation'] as $rid => $customCitation) {
            $citationsArray[$rid] = $customCitation;
        }

        $unifiedArray['fileId'][$_POST['xmlFilePath']] = $citationsArray;

        if ($_POST['publicationId']) {
            $citationJsonData = json_encode($unifiedArray);

            // Using DAO to update the setting
            $customPublicationSettingsDao = new CustomPublicationSettingsDAO();
            $customPublicationSettingsDao->updateSetting($_POST['publicationId'], 'jatsParser::citationTableData', $citationJsonData, $_POST['locale_key']);
            //echo '<pre>' . json_encode($unifiedArray, JSON_PRETTY_PRINT) . '</pre>';
        }
    
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
} 

?>