document.addEventListener('DOMContentLoaded', function () {
    // Function to collect all citation selections from the modal table and build JSON
    function getCitationsDataFromModal() {
        let form = document.getElementById('citationFormAll');
        if (!form) return null;

        let xmlFilePath = form.querySelector('input[name="xmlFilePath"]')?.value || '';
        let citationStyleName = form.querySelector('input[name="citationStyleName"]')?.value || 'apa';
        let publicationId = form.querySelector('input[name="publicationId"]')?.value || '';
        let localeKey = form.querySelector('input[name="locale_key"]')?.value || 'es';

        let citationsMap = {};
        form.querySelectorAll('.citation-select').forEach(function (select) {
            let xrefId = select.id.replace('citationStyle_', '');
            let val = select.value;
            if (val === 'custom') {
                let customInput = document.getElementById('customInput_' + xrefId);
                val = customInput ? customInput.value.trim() : '';
            }
            citationsMap[xrefId] = val;
        });

        return JSON.stringify({
            citationStyleName: citationStyleName,
            publicationId: publicationId,
            locale_key: localeKey,
            fileId: {
                [xmlFilePath]: citationsMap
            }
        });
    }

    // Function to save citations directly to the database via process_citations.php
    function saveCitationsToServer() {
        let container = document.getElementById('citationFormAll');
        if (!container) return;

        let xmlFilePath = container.querySelector('input[name="xmlFilePath"]')?.value || '';
        let citationStyleName = container.querySelector('input[name="citationStyleName"]')?.value || 'apa';
        let publicationId = container.querySelector('input[name="publicationId"]')?.value || '';
        let localeKey = container.querySelector('input[name="locale_key"]')?.value || 'es';

        let formData = new FormData();
        formData.append('xmlFilePath', xmlFilePath);
        formData.append('citationStyleName', citationStyleName);
        formData.append('publicationId', publicationId);
        formData.append('locale_key', localeKey);
        formData.append('ajax', '1');

        container.querySelectorAll('.citation-select').forEach(function (select) {
            let xrefId = select.id.replace('citationStyle_', '');
            let val = select.value;
            if (val === 'custom') {
                let customInput = document.getElementById('customInput_' + xrefId);
                val = customInput ? customInput.value.trim() : '';
                if (val !== '') {
                    formData.append('customCitation[' + xrefId + ']', val);
                }
            } else {
                formData.append('citationStyle[' + xrefId + ']', val);
            }
        });

        try {
            fetch('/plugins/generic/jatsParser/classes/components/forms/Helpers/process_citations.php', {
                method: 'POST',
                body: formData,
                keepalive: true
            }).catch(function (e) {
                console.error('Error saving citations:', e);
            });
        } catch (e) {
            console.error('Fetch error saving citations:', e);
        }
    }

    // Function to sync the citations JSON into the PKP form input and Vue model
    function syncCitationsToForm() {
        try {
            let json = getCitationsDataFromModal();
            if (!json) return;

            let form = document.getElementById('citationFormAll');
            let localeKey = form ? (form.querySelector('input[name="locale_key"]')?.value || 'es') : 'es';

            // 1. Update DOM inputs
            let targetInputs = document.querySelectorAll(
                'input[name="jatsParser::citationTableData[' + localeKey + ']"], ' +
                'input[name^="jatsParser::citationTableData[' + localeKey + ']"], ' +
                'input[name="jatsParser::citationTableData-' + localeKey + '"], ' +
                'input[name="jatsParser::citationTableData"], ' +
                'input[name^="jatsParser::citationTableData"]'
            );

            targetInputs.forEach(function (inp) {
                inp.value = json;
                inp.dispatchEvent(new Event('input', { bubbles: true }));
                inp.dispatchEvent(new Event('change', { bubbles: true }));
            });
        } catch (e) {
            console.error('syncCitationsToForm error:', e);
        }
    }

    // Sync on page load
    setTimeout(syncCitationsToForm, 300);

    // Modal open/close
    let openBtn = document.getElementById('openCitationModalBtn');
    let modal = document.getElementById('citationModal');
    let closeBtn = modal ? modal.querySelector('.citation-modal-close') : null;

    if (openBtn && modal) {
        openBtn.addEventListener('click', function () {
            modal.style.display = 'block';
            let defaultBtn = document.querySelector('.citation-tab-button.is-active') || document.querySelector('.citation-tab-button');
            if (defaultBtn) defaultBtn.click();
            syncCitationsToForm();
        });
    }

    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
        syncCitationsToForm();
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target.closest('.citation-modal-close-btn')) {
                closeModal();
            }
        });
    }

    // Tabs navigation
    document.addEventListener('click', function (e) {
        let btn = e.target.closest('.citation-tab-button');
        if (!btn) return;
        let target = btn.getAttribute('data-target');
        if (!target) return;
        document.querySelectorAll('.citation-tab-button').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
        });
        document.querySelectorAll('.citation-tab-panel').forEach(function (panel) {
            panel.classList.toggle('is-active', '#' + panel.id === target);
        });
    });

    if (!modal) return;

    // Delegación para selects
    modal.addEventListener('change', function (event) {
        if (!event.target.classList.contains('citation-select')) return;
        let selectElem = event.target;
        let xrefId = selectElem.id.replace('citationStyle_', '');
        let inputField = document.getElementById('customInput_' + xrefId);

        if (selectElem.value !== selectElem.getAttribute('data-original-value')) {
            selectElem.classList.remove('citation-original');
            selectElem.classList.add('citation-modified');
        } else {
            selectElem.classList.remove('citation-modified');
            selectElem.classList.add('citation-original');
        }

        if (selectElem.value === 'custom') {
            if (!inputField) {
                inputField = document.createElement('input');
                inputField.type = 'text';
                inputField.name = 'customCitation[' + xrefId + ']';
                inputField.id = 'customInput_' + xrefId;
                inputField.placeholder = 'ej: (González, 2011, p. 34)';
                inputField.className = 'custom-input';
                inputField.setAttribute('data-original-value', '');
                selectElem.parentNode.appendChild(inputField);
            }
        } else if (inputField) {
            inputField.remove();
        }

        syncCitationsToForm();
    });

    modal.addEventListener('input', function (event) {
        if (!event.target.classList.contains('custom-input')) return;
        let inputElem = event.target;

        if (inputElem.value.trim() === '') {
            inputElem.classList.add('citation-select-error');
        } else {
            inputElem.classList.remove('citation-select-error');
        }
        if (inputElem.value !== inputElem.getAttribute('data-original-value')) {
            inputElem.classList.remove('citation-original');
            inputElem.classList.add('citation-modified');
        } else {
            inputElem.classList.remove('citation-modified');
            inputElem.classList.add('citation-original');
        }

        syncCitationsToForm();
    });

    // Guardar cambios al presionar el botón general de Guardar de OJS
    function handleSaveTrigger(e) {
        try {
            saveCitationsToServer();
            syncCitationsToForm();
        } catch (err) {
            console.error('Error in handleSaveTrigger:', err);
        }
    }

    document.addEventListener('click', function (e) {
        let submitBtn = e.target.closest('button[type="submit"]') || 
            (e.target.matches && e.target.matches('button[type="submit"]') ? e.target : null) ||
            e.target.closest('.pkpButton--primary');
        if (submitBtn) {
            handleSaveTrigger(e);
        }
    }, true);

    document.addEventListener('submit', function (e) {
        handleSaveTrigger(e);
    }, true);
});