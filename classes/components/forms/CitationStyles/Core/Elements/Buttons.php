<?php namespace PKP\components\forms\CitationStyles\Core\Elements;

class Buttons {
    public static function getFormSaveButton() {
        return '</table>
                    <button type="submit" class="save-btn-citations">
                        ' . __('plugins.generic.jatsParser.citationtable.savebuttontext') . '
                    </button>
                </form>
                </div>';
    }
}