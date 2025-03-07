<?php

import('lib.pkp.classes.db.DAO');

class CustomPublicationSettingsDAO extends DAO {
    /**
     * Update jatsParser::citationTableData field in publication_settings table
     * First checks if the record exists, then either updates or inserts
     * @param int $publicationId
     * @param string $settingName
     * @param string $settingValue
     * @param string $localeKey
     */
    public function updateSetting($publicationId, $settingName, $settingValue, $localeKey = null) {
        try {
            // Check if the record exists
            $resultGenerator = $this->retrieve(
                'SELECT COUNT(*) as count FROM publication_settings 
                 WHERE publication_id = ? AND setting_name = ? AND locale = ?',
                [$publicationId, $settingName, $localeKey]
            );
            
            $results = iterator_to_array($resultGenerator);
            $exists = $results[0]->count > 0;
            
            if ($exists) {
                // Update existing record
                $this->update(
                    'UPDATE publication_settings 
                     SET setting_value = ?
                     WHERE publication_id = ? AND setting_name = ? AND locale = ?',
                    [$settingValue, $publicationId, $settingName, $localeKey]
                );
            } else {
                // Insert new record
                $this->update(
                    'INSERT INTO publication_settings 
                     (publication_id, setting_name, setting_value, locale)
                     VALUES (?, ?, ?, ?)',
                    [$publicationId, $settingName, $settingValue, $localeKey]
                );
            }
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
    public function getSetting($publicationId, $settingName, $localeKey) {
        try {

            $resultGenerator = $this->retrieve(
                'SELECT setting_value FROM publication_settings 
                 WHERE publication_id = ? AND setting_name = ? AND locale = ?',
                [$publicationId, $settingName, $localeKey]
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
