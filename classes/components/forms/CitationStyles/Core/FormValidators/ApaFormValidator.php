<?php namespace PKP\components\forms\CitationStyles\Core\FormValidators;

require_once __DIR__ . '/../../../Helpers/process_citations.php';

class ApaFormValidator{
    public static function onSubmitFunctions() {
        return 'let customInputs = document.querySelectorAll(\'.custom-input\');
                let hasEmptyCustomFields = false;
                            
                customInputs.forEach(function(input) {
                    if(input.value.trim() === \'\') {
                        hasEmptyCustomFields = true;
                        input.classList.add(\'citation-select-error\');
                    } else {
                        input.classList.remove(\'citation-select-error\');
                    }
                });
                            
                if(hasEmptyCustomFields) {
                    document.getElementById(\'citationErrorMessage\').style.display = \'block\';
                    return false;
                } else {
                    document.getElementById(\'citationErrorMessage\').style.display = \'none\';
                    window.location.reload(true);
                    let form = this;
                    let formData = new FormData(form);

                    fetch(\'./process_citations.php\', {
                        method: \'POST\',
                        body: formData
                    });
                }';
    }
}