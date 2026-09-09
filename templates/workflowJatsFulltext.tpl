<link rel="stylesheet" type="text/css" href="/plugins/generic/jatsParser/app/citationTable.css?v=2" />
<script src="/plugins/generic/jatsParser/app/citationTable.js?v=2"></script>

<tab id="jatsUpload" label="{translate key="plugins.generic.jatsParser.publication.jats.fulltext"}">
    <pkp-form v-bind="components.{$smarty.const.FORM_PUBLICATION_JATS_FULLTEXT}" @set="set" />
</tab>

<!-- Modal nativo de confirmación para eliminar HTML de publicación (fuera del tab para persistir en el DOM) -->
<div id="jatsDeleteModal" class="citation-modal" style="display: none;">
    <div class="citation-modal-content" style="max-width: 500px; margin: 10% auto; background: #fff; border-radius: 4px; padding: 24px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); border: 1px solid rgba(0,0,0,0.15); position: relative;">
        <span class="citation-modal-close" onclick="jatsCloseDeleteModal()" style="position: absolute; right: 18px; top: 14px; font-size: 24px; color: #888; cursor: pointer; line-height: 1;">&times;</span>
        <h2 style="margin-top: 0; margin-bottom: 14px; font-size: 1.15rem; font-weight: 700; color: #222;">
            {translate key="plugins.generic.jatsParser.publication.jats.html.deleteConfirmTitle"}
        </h2>
        <p style="margin-bottom: 12px; font-size: 0.9rem; line-height: 1.45; color: #444;">
            {translate key="plugins.generic.jatsParser.publication.jats.html.deleteConfirmText"}
        </p>
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e5e5;">
            <button type="button" class="pkpButton" onclick="jatsCloseDeleteModal()">
                {translate key="common.cancel"}
            </button>
            <button type="button" id="jatsConfirmDeleteBtn" class="pkpButton pkpButton--isWarnable" onclick="jatsConfirmDeleteHtml()">
                {translate key="plugins.generic.jatsParser.publication.jats.html.deleteBtn"}
            </button>
        </div>
    </div>
</div>

<script>
    window.jatsPublicationApiUrl = {$jatsPublicationApiUrl|json_encode};

    var jatsTargetDeleteLocale = null;

    function jatsOpenDeleteModal(locale, localeName) {
        if (locale) jatsTargetDeleteLocale = locale;
        var modal = document.getElementById('jatsDeleteModal');
        if (modal) {
            var targetText = modal.querySelector('p');
            if (targetText && localeName) {
                targetText.innerText = {translate|json_encode key="plugins.generic.jatsParser.publication.jats.html.deleteConfirmText"} + ' (' + localeName + ')';
            }
            modal.style.display = 'block';
        }
    }

    function jatsCloseDeleteModal() {
        var modal = document.getElementById('jatsDeleteModal');
        if (modal) modal.style.display = 'none';
    }

    // Delegación de eventos de clic para el botón de eliminar HTML
    document.addEventListener('click', function(e) {
        var btn = e.target ? e.target.closest('.jatsDeleteHtmlBtn, #jatsDeleteHtmlBtn') : null;
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            var locale = btn.dataset.locale || (window.pkp && window.pkp.context ? window.pkp.context.currentLocale : 'es');
            var localeName = btn.dataset.localename || locale;
            jatsOpenDeleteModal(locale, localeName);
        }
    });

    function jatsConfirmDeleteHtml() {
        var btn = document.getElementById('jatsConfirmDeleteBtn');
        if (btn) btn.disabled = true;

        var activeLocale = jatsTargetDeleteLocale;
        if (!activeLocale) {
            var activeLangBtn = document.querySelector('.pkpFormLocales .pkpButton--isPrimary, .pkpFormLocales .isCurrent');
            if (activeLangBtn && activeLangBtn.dataset && activeLangBtn.dataset.locale) {
                activeLocale = activeLangBtn.dataset.locale;
            } else {
                activeLocale = (window.pkp && window.pkp.context && window.pkp.context.currentLocale) ? window.pkp.context.currentLocale : 'es';
            }
        }

        var payload = {
            'jatsParser::deleteHtml': {}
        };
        payload['jatsParser::deleteHtml'][activeLocale] = true;

        var csrfToken = (window.pkp && window.pkp.currentUser && window.pkp.currentUser.csrfToken)
            ? window.pkp.currentUser.csrfToken
            : (document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '');

        fetch(window.jatsPublicationApiUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Csrf-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Error al eliminar HTML');
            return response.json();
        })
        .then(function(data) {
            if (window.pkp && window.pkp.eventBus) {
                window.pkp.eventBus.$emit('notify', {translate|json_encode key="plugins.generic.jatsParser.publication.jats.html.deletedSuccess"}, 'success');
            }
            jatsCloseDeleteModal();
            sessionStorage.setItem('jats_reload_active', '1');
            setTimeout(function() {
                window.location.reload();
            }, 600);
        })
        .catch(function(err) {
            alert('Error: ' + err.message);
            if (btn) btn.disabled = false;
        });
    }

    // Restaurar automáticamente la pestaña JATSParser si se recargó la página tras guardar o eliminar
    if (sessionStorage.getItem('jats_reload_active')) {
        sessionStorage.removeItem('jats_reload_active');
        var jatsRestoreAttempts = 0;
        var jatsRestoreInterval = setInterval(function() {
            jatsRestoreAttempts++;
            var pubBtn = document.getElementById('publication-button');
            if (pubBtn) {
                pubBtn.click();
                var jatsBtn = document.getElementById('jatsUpload-button');
                if (jatsBtn) {
                    jatsBtn.click();
                    clearInterval(jatsRestoreInterval);
                }
            }
            if (jatsRestoreAttempts > 40) {
                clearInterval(jatsRestoreInterval);
            }
        }, 100);
    }

    // Escuchar el evento form-success nativo de OJS para recargar al presionar "Guardar"
    if (window.pkp && window.pkp.eventBus) {
        window.pkp.eventBus.$on('form-success', function(formId, response) {
            if (formId === 'jatsUpload') {
                sessionStorage.setItem('jats_reload_active', '1');
                setTimeout(function() {
                    window.location.reload();
                }, 600);
            }
        });
    }
</script>