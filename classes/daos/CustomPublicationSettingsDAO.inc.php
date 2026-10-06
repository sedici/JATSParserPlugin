<?php

class CustomPublicationSettingsDAO {
    /**
     * Update setting in publication_settings table
     * @param int $publicationId
     * @param string $settingName
     * @param string $settingValue
     * @param string $localeKey
     */
    public function updateSetting($publicationId, $settingName, $settingValue, $localeKey = null) {
        try {
            $exists = \Illuminate\Support\Facades\DB::table('publication_settings')
                ->where('publication_id', '=', (int)$publicationId)
                ->where('setting_name', '=', $settingName)
                ->where('locale', '=', $localeKey)
                ->exists();

            if ($exists) {
                \Illuminate\Support\Facades\DB::table('publication_settings')
                    ->where('publication_id', '=', (int)$publicationId)
                    ->where('setting_name', '=', $settingName)
                    ->where('locale', '=', $localeKey)
                    ->update(['setting_value' => $settingValue]);
            } else {
                \Illuminate\Support\Facades\DB::table('publication_settings')->insert([
                    'publication_id' => (int)$publicationId,
                    'setting_name' => $settingName,
                    'setting_value' => $settingValue,
                    'locale' => $localeKey,
                ]);
            }
        } catch (\Exception $e) {
            error_log("CustomPublicationSettingsDAO updateSetting error: " . $e->getMessage());
        }
    }

    /**
     * Get setting from publication_settings table
     * @param int $publicationId
     * @param string $settingName
     * @param string $localeKey
     * @return array|string|null
     */
    public function getSetting($publicationId, $settingName, $localeKey = null) {
        try {
            $query = \Illuminate\Support\Facades\DB::table('publication_settings')
                ->where('publication_id', '=', (int)$publicationId)
                ->where('setting_name', '=', $settingName);

            if ($localeKey !== null) {
                $query->where('locale', '=', $localeKey);
            }

            $record = $query->first();

            if ($record && isset($record->setting_value)) {
                $decoded = json_decode($record->setting_value, true);
                return $decoded !== null ? $decoded : $record->setting_value;
            }

            return null;
        } catch (\Exception $e) {
            error_log("CustomPublicationSettingsDAO getSetting error: " . $e->getMessage());
            return null;
        }
    }
}

