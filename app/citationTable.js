
document.addEventListener('DOMContentLoaded', function() {
    // Modal open/close
    var openBtn = document.getElementById('openCitationModalBtn');
    var modal = document.getElementById('citationModal');
    var closeBtn = modal ? modal.querySelector('.citation-modal-close') : null;

    if (openBtn && modal) {
        openBtn.addEventListener('click', function() {
            modal.style.display = 'block';
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

    // --- Lógica con delegación de eventos para elementos dinámicos ---

    // 1. Delegación para los eventos 'change' de .citation-select (incluyendo los que se añadan dinámicamente, aunque no debería ser el caso aquí, es buena práctica)
    // Adjuntamos el oyente al formulario completo, ya que los selects están dentro de él.
    var citationForm = document.getElementById('citationForm'); // Obtenemos el formulario una sola vez

    if (citationForm) { // Asegurarse de que el formulario existe
        citationForm.addEventListener('change', function(event) {
            // Verificamos si el evento se originó en un elemento con la clase 'citation-select'
            if (event.target.classList.contains('citation-select')) {
                var selectElem = event.target;
                var xrefId = selectElem.id.replace('citationStyle_', '');
                var inputField = document.getElementById('customInput_' + xrefId);

                // Estilo del select
                if (selectElem.value !== selectElem.getAttribute('data-original-value')) {
                    selectElem.classList.remove('citation-original');
                    selectElem.classList.add('citation-modified');
                } else {
                    selectElem.classList.remove('citation-modified');
                    selectElem.classList.add('citation-original');
                }

                // Mostrar/ocultar input personalizado
                if (selectElem.value === 'custom') {
                    if (!inputField) {
                        inputField = document.createElement('input');
                        inputField.type = 'text';
                        inputField.name = 'customCitation[' + xrefId + ']';
                        inputField.id = 'customInput_' + xrefId;
                        inputField.placeholder = 'ej: (González, 2011, p. 34)';
                        inputField.className = 'custom-input';
                        // Si el valor original debe ser vacío para un campo nuevo, está bien.
                        // Si quieres que retenga un valor 'original' específico para un nuevo campo, configúralo aquí.
                        inputField.setAttribute('data-original-value', ''); 
                        selectElem.parentNode.appendChild(inputField);

                        // NO es necesario añadir un event listener 'input' aquí.
                        // La delegación de eventos en el paso 2 se encargará de ello.
                    }
                } else {
                    if (inputField) {
                        inputField.remove();
                    }
                }
            }
        });

        // 2. Delegación para los eventos 'input' de .custom-input (especialmente los creados dinámicamente)
        // También adjuntamos el oyente al formulario
        citationForm.addEventListener('input', function(event) {
            // Verificamos si el evento se originó en un elemento con la clase 'custom-input'
            if (event.target.classList.contains('custom-input')) {
                var inputElem = event.target;
                if (inputElem.value.trim() === '') {
                    inputElem.classList.add('citation-select-error');
                } else {
                    inputElem.classList.remove('citation-select-error');
                }
                // Estilo del input
                if (inputElem.value !== inputElem.getAttribute('data-original-value')) {
                    inputElem.classList.remove('citation-original');
                    inputElem.classList.add('citation-modified');
                } else {
                    inputElem.classList.remove('citation-modified');
                    inputElem.classList.add('citation-original');
                }
            }
        });

        // 3. Lógica de validación y envío del formulario (ya está bien estructurada, solo reconfirmamos)
        citationForm.addEventListener('submit', function(e) {
            var customInputs = citationForm.querySelectorAll('.custom-input');
            var hasEmptyCustomFields = false;
            customInputs.forEach(function(input) {
                if (input.value.trim() === '') {
                    hasEmptyCustomFields = true;
                    input.classList.add('citation-select-error');
                } else {
                    input.classList.remove('citation-select-error');
                }
            });
            var errorMsg = document.getElementById('citationErrorMessage');
            if (hasEmptyCustomFields) {
                if (errorMsg) errorMsg.style.display = 'block';
                e.preventDefault(); // Previene el envío del formulario
                return false;
            } else {
                if (errorMsg) errorMsg.style.display = 'none';
            }
        });
    }
});