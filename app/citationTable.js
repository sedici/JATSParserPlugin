document.addEventListener('DOMContentLoaded', function () {
    // Function to collect all citation selections from a specific modal table and build JSON
    function getCitationsDataFromModal(targetLocale) {
        let form = null;
        if (targetLocale) {
            form = document.getElementById('citationForm_' + targetLocale);
        }
        if (!form) {
            form = document.querySelector('.citation-form') || document.getElementById('citationFormAll');
        }
        if (!form) return null;

        let xmlFilePath = form.querySelector('input[name="xmlFilePath"]')?.value || '';
        let citationStyleName = form.querySelector('input[name="citationStyleName"]')?.value || 'apa';
        let publicationId = form.querySelector('input[name="publicationId"]')?.value || '';
        let localeKey = form.querySelector('input[name="locale_key"]')?.value || targetLocale || 'es';

        let citationsMap = {};
        form.querySelectorAll('.citation-select').forEach(function (select) {
            let nameAttr = select.getAttribute('name') || '';
            let xrefId = '';
            let match = nameAttr.match(/\[([^\]]+)\]/);
            if (match && match[1]) {
                xrefId = match[1];
            } else {
                xrefId = select.id.replace('citationStyle_', '').replace(localeKey + '_', '');
            }

            let val = select.value;
            if (val === 'custom') {
                let customInput = select.parentNode ? select.parentNode.querySelector('.custom-input') : document.getElementById('customInput_' + xrefId);
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

    // Function to save citations directly to the database via OJS REST API
    function saveCitationsToServer(targetLocale) {
        let apiUrl = window.jatsPublicationApiUrl;
        if (!apiUrl) {
            let app = window.pkp && window.pkp.registry && window.pkp.registry._instances && window.pkp.registry._instances['app'];
            if (app && app.components && app.components['jatsUpload'] && app.components['jatsUpload'].action) {
                apiUrl = app.components['jatsUpload'].action;
            }
        }
        if (!apiUrl) {
            console.warn('jatsPublicationApiUrl is not defined, skipping REST API save.');
            return Promise.resolve();
        }

        let form = targetLocale ? document.getElementById('citationForm_' + targetLocale) : (document.querySelector('.citation-form') || document.getElementById('citationFormAll'));
        let localeKey = form ? (form.querySelector('input[name="locale_key"]')?.value || targetLocale || 'es') : (targetLocale || 'es');

        let json = getCitationsDataFromModal(localeKey);
        if (!json) return Promise.resolve();

        let payload = {
            'jatsParser::citationTableData': {}
        };
        payload['jatsParser::citationTableData'][localeKey] = json;

        let csrfToken = (window.pkp && window.pkp.currentUser && window.pkp.currentUser.csrfToken)
            ? window.pkp.currentUser.csrfToken
            : (document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '');

        try {
            return fetch(apiUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Csrf-Token': csrfToken
                },
                body: JSON.stringify(payload),
                keepalive: true
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error(response.statusText || 'Request failed');
                }
                return response.json();
            }).catch(function (e) {
                console.error('Error saving citations via REST API:', e);
            });
        } catch (e) {
            console.error('Fetch error saving citations via REST API:', e);
            return Promise.reject(e);
        }
    }

    // Expose globally if needed
    window.jatsSaveCitationsToServer = saveCitationsToServer;

    // Function to sync the citations JSON into the PKP form input and Vue model
    function syncCitationsToForm(targetLocale) {
        try {
            let forms = [];
            if (targetLocale) {
                let f = document.getElementById('citationForm_' + targetLocale);
                if (f) forms.push(f);
            }
            if (!forms.length) {
                forms = Array.from(document.querySelectorAll('.citation-form'));
                if (!forms.length) {
                    let f = document.getElementById('citationFormAll');
                    if (f) forms.push(f);
                }
            }

            forms.forEach(function (form) {
                let localeKey = form.querySelector('input[name="locale_key"]')?.value || targetLocale || 'es';
                let json = getCitationsDataFromModal(localeKey);
                if (!json) return;

                // 1. Update Vue form component hiddenFields (OJS 3.4 FormComponent)
                let app = window.pkp && window.pkp.registry && window.pkp.registry._instances && window.pkp.registry._instances['app'];
                if (app && app.components && app.components['jatsUpload']) {
                    let jatsForm = app.components['jatsUpload'];
                    if (!jatsForm.hiddenFields) {
                        jatsForm.hiddenFields = {};
                    }
                    if (typeof jatsForm.hiddenFields['jatsParser::citationTableData'] !== 'object' || jatsForm.hiddenFields['jatsParser::citationTableData'] === null) {
                        jatsForm.hiddenFields['jatsParser::citationTableData'] = {};
                    }
                    jatsForm.hiddenFields['jatsParser::citationTableData'][localeKey] = json;
                }

                // 2. Also update DOM inputs if any exist
                let targetInputs = document.querySelectorAll(
                    'input[name="jatsParser::citationTableData[' + localeKey + ']"], ' +
                    'input[name^="jatsParser::citationTableData[' + localeKey + ']"], ' +
                    'input[name="jatsParser::citationTableData-' + localeKey + '"]'
                );

                targetInputs.forEach(function (inp) {
                    inp.value = json;
                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        } catch (e) {
            console.error('syncCitationsToForm error:', e);
        }
    }

    // Sync on page load
    setTimeout(function() { syncCitationsToForm(); }, 300);

    // ========================================================
    // SYNCHRONIZATION WITH OJS TOP-RIGHT LANGUAGE SWITCHER
    // ========================================================
    function updateCitationColumnsVisibility() {
        let wrapper = document.querySelector('.jats-citation-multilingual-wrapper');
        if (!wrapper) return;

        let localeCols = wrapper.querySelectorAll('.jats-citation-locale');
        if (!localeCols.length) return;

        // 1. Determine active secondary locale from Vue if available
        let activeSecondaryLocale = null;
        let primaryLocale = null;

        try {
            let app = window.pkp && window.pkp.registry && window.pkp.registry._instances && window.pkp.registry._instances['app'];
            if (app && app.components && app.components['jatsUpload']) {
                let jatsForm = app.components['jatsUpload'];
                primaryLocale = jatsForm.primaryLocale;
                let visible = jatsForm.visibleLocales || [];
                for (let i = 0; i < visible.length; i++) {
                    if (visible[i] !== primaryLocale) {
                        activeSecondaryLocale = visible[i];
                        break;
                    }
                }
            }
        } catch (err) {}

        // 2. If not determined via Vue, inspect .pkpFormLocales in the DOM
        if (!activeSecondaryLocale) {
            let activeBtn = document.querySelector('.pkpFormLocales .pkpButton--isPrimary, .pkpFormLocales .isCurrent, .pkpFormLocales [aria-selected="true"], .pkpFormLocales .pkpFormLocales__locale--isCurrent');
            if (activeBtn) {
                let loc = activeBtn.dataset.locale || activeBtn.getAttribute('data-locale');
                if (loc) {
                    let primCol = wrapper.querySelector('.jats-citation-locale--primary, [data-is-primary="1"]');
                    let primKey = primCol ? primCol.getAttribute('data-locale') : primaryLocale;
                    if (loc !== primKey) {
                        activeSecondaryLocale = loc;
                    }
                }
            }
        }

        // 3. Fallback: inspect any visible locale group in Section 1 or Section 3
        if (!activeSecondaryLocale) {
            let visibleGroupLocales = document.querySelectorAll('.pkpFormGroup__fields .pkpFormGroup__locale--isVisible');
            visibleGroupLocales.forEach(function (el) {
                if (el.closest('.jats-citation-multilingual-wrapper')) return;
                let inp = el.querySelector('input[name*="-"], input[name*="["]');
                if (inp) {
                    let m = inp.name.match(/-([a-zA-Z_]+)$/) || inp.name.match(/\[([a-zA-Z_]+)\]$/);
                    let primCol = wrapper.querySelector('.jats-citation-locale--primary, [data-is-primary="1"]');
                    let primKey = primCol ? primCol.getAttribute('data-locale') : primaryLocale;
                    if (m && m[1] && m[1] !== primKey) {
                        activeSecondaryLocale = m[1];
                    }
                }
            });
        }

        // Apply visibility to columns
        if (activeSecondaryLocale) {
            wrapper.classList.add('has-secondary-visible');
            localeCols.forEach(function (col) {
                let colLoc = col.getAttribute('data-locale');
                let isPrim = col.getAttribute('data-is-primary') === '1';
                if (isPrim) {
                    col.style.display = '';
                } else if (colLoc === activeSecondaryLocale) {
                    col.classList.add('is-active-locale');
                    col.style.display = '';
                } else {
                    col.classList.remove('is-active-locale');
                    col.style.display = 'none';
                }
            });
        } else {
            wrapper.classList.remove('has-secondary-visible');
            localeCols.forEach(function (col) {
                let isPrim = col.getAttribute('data-is-primary') === '1';
                if (isPrim) {
                    col.style.display = '';
                } else {
                    col.classList.remove('is-active-locale');
                    col.style.display = 'none';
                }
            });
        }
    }

    // Run column visibility sync on load and after small delays
    updateCitationColumnsVisibility();
    setTimeout(updateCitationColumnsVisibility, 150);
    setTimeout(updateCitationColumnsVisibility, 400);
    setTimeout(updateCitationColumnsVisibility, 900);

    // Listen to clicks on the top-right locale switcher tabs
    document.addEventListener('click', function (e) {
        if (e.target.closest('.pkpFormLocales') || e.target.closest('.pkpFormLocales__locale')) {
            setTimeout(updateCitationColumnsVisibility, 50);
            setTimeout(updateCitationColumnsVisibility, 200);
        }
    });

    // Observer for top-right locale switcher DOM changes
    let localesContainer = document.querySelector('.pkpFormLocales');
    if (localesContainer) {
        let obs = new MutationObserver(function () {
            updateCitationColumnsVisibility();
        });
        obs.observe(localesContainer, { attributes: true, subtree: true, attributeFilter: ['class', 'aria-selected', 'aria-pressed'] });
    }

    // ========================================================
    // MODAL OPEN / CLOSE (SCOPED PER LOCALE)
    // ========================================================
    document.addEventListener('click', function (e) {
        let openBtn = e.target.closest('.view-btn-citations, #openCitationModalBtn');
        if (!openBtn) return;
        e.preventDefault();

        let locale = openBtn.getAttribute('data-locale');
        let targetModal = locale ? document.getElementById('citationModal_' + locale) : document.getElementById('citationModal');
        if (!targetModal && locale) {
            targetModal = document.querySelector('.citation-modal[data-locale="' + locale + '"]');
        }
        if (!targetModal) {
            targetModal = document.getElementById('citationModal') || document.querySelector('.citation-modal');
        }
        if (targetModal) {
            targetModal.style.display = 'block';
            let defaultBtn = targetModal.querySelector('.citation-tab-button.is-active') || targetModal.querySelector('.citation-tab-button');
            if (defaultBtn) defaultBtn.click();
            syncCitationsToForm(locale);
        }
    });

    function closeModal(targetModal) {
        if (!targetModal) {
            document.querySelectorAll('.citation-modal').forEach(function (m) {
                m.style.display = 'none';
            });
            syncCitationsToForm();
            return;
        }
        targetModal.style.display = 'none';
        let locale = targetModal.getAttribute('data-locale');
        syncCitationsToForm(locale);
    }

    document.addEventListener('click', function (e) {
        let closeBtn = e.target.closest('.citation-modal-close, .citation-modal-close-btn');
        if (closeBtn) {
            let m = closeBtn.closest('.citation-modal');
            closeModal(m);
            return;
        }
        if (e.target.classList && e.target.classList.contains('citation-modal')) {
            closeModal(e.target);
        }
    });

    // Modal Tabs navigation
    document.addEventListener('click', function (e) {
        let btn = e.target.closest('.citation-tab-button');
        if (!btn) return;
        let target = btn.getAttribute('data-target');
        if (!target) return;
        let modalContainer = btn.closest('.citation-modal') || document;
        modalContainer.querySelectorAll('.citation-tab-button').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
        });
        modalContainer.querySelectorAll('.citation-tab-panel').forEach(function (panel) {
            panel.classList.toggle('is-active', '#' + panel.id === target);
        });
    });

    // ========================================================
    // SELECT & INPUT CHANGES
    // ========================================================
    document.addEventListener('change', function (event) {
        if (!event.target.classList.contains('citation-select')) return;
        let selectElem = event.target;
        let parentCell = selectElem.parentNode;
        let inputField = parentCell ? parentCell.querySelector('.custom-input') : null;

        if (selectElem.value !== selectElem.getAttribute('data-original-value')) {
            selectElem.classList.remove('citation-original');
            selectElem.classList.add('citation-modified');
        } else {
            selectElem.classList.remove('citation-modified');
            selectElem.classList.add('citation-original');
        }

        let nameAttr = selectElem.getAttribute('name') || '';
        let xrefId = '';
        let match = nameAttr.match(/\[([^\]]+)\]/);
        if (match && match[1]) {
            xrefId = match[1];
        } else {
            xrefId = selectElem.id.replace('citationStyle_', '');
        }

        if (selectElem.value === 'custom') {
            if (!inputField && parentCell) {
                inputField = document.createElement('input');
                inputField.type = 'text';
                inputField.name = 'customCitation[' + xrefId + ']';
                inputField.id = 'customInput_' + xrefId;
                inputField.placeholder = 'ej: (González, 2011, p. 34)';
                inputField.className = 'custom-input';
                inputField.setAttribute('data-original-value', '');
                parentCell.appendChild(inputField);
            }
        } else if (inputField) {
            inputField.remove();
        }

        let modalElem = selectElem.closest('.citation-modal');
        let loc = modalElem ? modalElem.getAttribute('data-locale') : null;
        syncCitationsToForm(loc);
    });

    document.addEventListener('input', function (event) {
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

        let modalElem = inputElem.closest('.citation-modal');
        let loc = modalElem ? modalElem.getAttribute('data-locale') : null;
        syncCitationsToForm(loc);
    });

    // Sincronizar cambios en el formulario OJS antes de enviar
    function handleSaveTrigger() {
        try {
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
            handleSaveTrigger();
        }
    }, true);

    document.addEventListener('submit', function () {
        handleSaveTrigger();
    }, true);
});