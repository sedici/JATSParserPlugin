document.addEventListener('DOMContentLoaded', function() {
    // Modal open/close
    var openBtn = document.getElementById('openCitationModalBtn');
    var modal = document.getElementById('citationModal');
    var closeBtn = modal ? modal.querySelector('.citation-modal-close') : null;

    if (openBtn && modal) {
        openBtn.addEventListener('click', function() {
            modal.style.display = 'block';
            // Activar pestaña por defecto
            var defaultBtn = document.querySelector('.citation-tab-button.is-active') || document.querySelector('.citation-tab-button');
            if (defaultBtn) defaultBtn.click();
        });
    }
    if (closeBtn && modal) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    // Tabs delegados
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.citation-tab-button');
        if (!btn) return;
        var target = btn.getAttribute('data-target');
        if (!target) return;
        document.querySelectorAll('.citation-tab-button').forEach(function(b){
            b.classList.toggle('is-active', b === btn);
        });
        document.querySelectorAll('.citation-tab-panel').forEach(function(panel){
            panel.classList.toggle('is-active', '#' + panel.id === target);
        });
    });

    // Spinner styles
    if (!document.getElementById('citation-saving-style')) {
        var style = document.createElement('style');
        style.id = 'citation-saving-style';
        style.textContent = `
.citation-saving-spinner{margin-left:8px;width:16px;height:16px;border:2px solid rgba(0,0,0,.2);border-top-color:rgba(0,0,0,.7);border-radius:50%;display:inline-block;vertical-align:middle;animation:citation-spin 1s linear infinite}
.citation-saving-disabled{opacity:.6;cursor:not-allowed}
@keyframes citation-spin{to{transform:rotate(360deg)}}`;
        document.head.appendChild(style);
    }

    if (!modal) return;

    // Delegación para selects
    modal.addEventListener('change', function(event){
        if (!event.target.classList.contains('citation-select')) return;
        var selectElem = event.target;
        var xrefId = selectElem.id.replace('citationStyle_', '');
        var inputField = document.getElementById('customInput_' + xrefId);
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
    });

    modal.addEventListener('input', function(event){
        if (!event.target.classList.contains('custom-input')) return;
        var inputElem = event.target;
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
    });

    // Submit único
    modal.addEventListener('submit', function(e){
        var form = e.target.closest('form#citationFormAll');
        if (!form) return;
        var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        var savingSpinner = form.querySelector('.citation-saving-spinner');
        if (!savingSpinner && submitBtn) {
            savingSpinner = document.createElement('span');
            savingSpinner.className = 'citation-saving-spinner';
            savingSpinner.style.display = 'none';
            submitBtn.insertAdjacentElement('afterend', savingSpinner);
        }
        var hasEmpty = false;
        form.querySelectorAll('.custom-input').forEach(function(input){
            if (input.value.trim() === '') {
                hasEmpty = true;
                input.classList.add('citation-select-error');
            } else {
                input.classList.remove('citation-select-error');
            }
        });
        var errorMsg = document.getElementById('citationErrorMessage');
        if (hasEmpty) {
            if (errorMsg) errorMsg.style.display = 'block';
            if (submitBtn && savingSpinner) {
                savingSpinner.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.classList.remove('citation-saving-disabled');
            }
            e.preventDefault();
            return false;
        } else {
            if (errorMsg) errorMsg.style.display = 'none';
            if (submitBtn && savingSpinner) {
                submitBtn.disabled = true;
                submitBtn.classList.add('citation-saving-disabled');
                savingSpinner.style.display = 'inline-block';
            }
        }
    });
});