<?php

import('lib.pkp.classes.db.DAO');

class CustomPublicationSettingsDAO extends DAO {
    /**
     * Update jatsParser::citationTableData field in publication_settings table
     * @param int $publicationId
     * @param string $settingName
     * @param string $settingValue
     */
    public function updateSetting($publicationId, $settingName, $settingValue) {
        try {
            $this->update(
                'UPDATE publication_settings 
                 SET setting_value = ? 
                 WHERE publication_id = ? AND setting_name = ?',
                [$settingValue, $publicationId, $settingName]
            );
        } catch (Exception $e) {
            error_log("Error updating: " . $e->getMessage());
            print_r("Error updating: " . $e->getMessage());
        }
    }





    /**
     * Get jatsParser::citationTableData field from publication_settings table
     * @param int $publicationId
     * @param string $settingName
     * @return string|null
     */
    public function getSetting($publicationId, $settingName) {
        try {

            $resultGenerator = $this->retrieve(
                'SELECT setting_value FROM publication_settings 
                 WHERE publication_id = ? AND setting_name = ?',
                [$publicationId, $settingName]
            );

            $results = iterator_to_array($resultGenerator);

            if (!empty($results)) {

                $firstRow = reset($results);
                $settingValue = $firstRow->setting_value;
                
                $decodedValue = json_decode($settingValue, true);

                return $decodedValue;
            } else {
                return null;
            }
        } catch (Exception $e) {
            print_r("Error al obtener el campo personalizado: " . $e->getMessage());
            return null;
        }
    }
}
?>
