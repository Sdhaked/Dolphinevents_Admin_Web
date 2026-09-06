(() => {
    const toggle = document.getElementById('serviceReserved');
    const section = document.getElementById('reservedFieldsSection');
    if (!toggle || !section) return;

    const list = document.getElementById('reservedFieldsList');
    const template = document.getElementById('reservedFieldTemplate');
    const addButton = document.getElementById('addReservedField');
    const empty = document.getElementById('reservedFieldsEmpty');
    const error = document.getElementById('reservedFieldsError');
    const confirmation = document.getElementById('reservedFieldsConfirmation');
    const dialog = document.querySelector('#serviceModal .modal-dialog');
    let pendingConfirmation = null;
    let nextId = 0;

    const rows = () => [...list.querySelectorAll('.reserved-field')];
    const input = (row, key) => row.querySelector(`[data-property="${key}"]`);

    function showError(message = '') {
        error.textContent = message;
        error.hidden = !message;
    }

    function answerConfirmation(answer) {
        confirmation.hidden = true;
        const resolve = pendingConfirmation;
        pendingConfirmation = null;
        resolve?.(answer);
    }

    function confirmChange(title, message, action) {
        if (pendingConfirmation) return Promise.resolve(false);
        confirmation.querySelector('[data-confirm-title]').textContent = title;
        confirmation.querySelector('[data-confirm-message]').textContent = message;
        confirmation.querySelector('[data-confirm-accept]').textContent = action;
        confirmation.hidden = false;
        confirmation.querySelector('[data-confirm-cancel]').focus();
        return new Promise(resolve => { pendingConfirmation = resolve; });
    }

    confirmation.querySelector('[data-confirm-cancel]').addEventListener('click', () => answerConfirmation(false));
    confirmation.querySelector('[data-confirm-accept]').addEventListener('click', () => answerConfirmation(true));

    function syncRow(row) {
        const type = input(row, 'field_type').value;
        const validation = input(row, 'validation_type').value;
        const choices = ['dropdown', 'radio'].includes(type);
        const numeric = type === 'number' || validation === 'number';
        const custom = validation === 'custom';
        row.querySelector('[data-options-group]').hidden = !choices;
        row.querySelector('[data-pattern-group]').hidden = !custom;
        row.querySelector('[data-number-group]').hidden = !numeric;
        row.querySelector('[data-length-group]').hidden = !['text', 'textarea', 'email', 'phone'].includes(type);
        row.querySelector('[data-validation-help]').textContent = {
            email: 'Example: name@example.com',
            phone: '10-digit Indian mobile number, for example 9876543210.',
            vehicle_number: 'Vehicle format is configured automatically. Example: RJ14AB1234.',
            url: 'Example: https://example.com',
        }[validation] || '';

        row.querySelectorAll('[data-property]').forEach(control => {
            control.disabled = !toggle.checked || Boolean(control.closest('[hidden]'));
        });
        input(row, 'options').required = choices && toggle.checked;
        input(row, 'validation_pattern').required = custom && toggle.checked;
    }

    function sync() {
        section.hidden = !toggle.checked;
        dialog.classList.toggle('modal-lg', toggle.checked);
        rows().forEach((row, index) => {
            row.querySelector('[data-field-heading]').textContent = `Field ${index + 1}`;
            row.querySelectorAll('[data-property]').forEach(control => {
                control.name = `reserved_fields[${index}][${control.dataset.property}]`;
            });
            syncRow(row);
        });
        empty.hidden = rows().length > 0;
        addButton.disabled = rows().length >= 50;
    }

    function validateRow(row, showInvalid = false) {
        row.querySelectorAll('[data-property]').forEach(control => control.setCustomValidity(''));
        const label = input(row, 'field_label');
        if (!label.value.trim()) label.setCustomValidity('Enter a field label.');
        const options = input(row, 'options');
        if (!options.disabled) {
            const values = options.value.split(/\r?\n/).map(value => value.trim()).filter(Boolean);
            let message = '';
            if (!values.length) message = 'Enter at least one option, one per line.';
            else if (values.length > 100) message = 'Use no more than 100 options.';
            else if (values.some(value => value.length > 255)) message = 'Each option can have up to 255 characters.';
            else if (new Set(values).size !== values.length) message = 'Each option must be unique.';
            options.setCustomValidity(message);
            options.nextElementSibling.textContent = message || 'Enter at least one option, one per line.';
        }
        const pattern = input(row, 'validation_pattern');
        if (!pattern.disabled) {
            try {
                if (!pattern.value.trim()) throw new Error('empty');
                new RegExp(pattern.value);
            } catch {
                pattern.setCustomValidity('Enter a valid validation pattern.');
            }
        }
        const min = input(row, 'min_value');
        const max = input(row, 'max_value');
        if (!max.disabled && min.value !== '' && max.value !== '' && Number(max.value) < Number(min.value)) {
            max.setCustomValidity('Maximum must be at least Minimum.');
        }
        let valid = true;
        row.querySelectorAll('[data-property]').forEach(control => {
            const invalid = !control.disabled && !control.validity.valid;
            if (showInvalid || !invalid) control.classList.toggle('is-invalid', invalid);
            if (invalid) valid = false;
        });
        return valid;
    }

    function addField(data = {}, focus = false) {
        if (rows().length >= 50) return;
        const row = template.content.firstElementChild.cloneNode(true);
        const prefix = `reserved-field-${nextId++}`;
        row.querySelectorAll('[data-property]').forEach(control => {
            const key = control.dataset.property;
            control.id = `${prefix}-${key}`;
            row.querySelector(`[data-label-for="${key}"]`)?.setAttribute('for', control.id);
            if (control.type === 'checkbox') {
                control.checked = [true, 1, '1'].includes(data[key]);
            } else if (key === 'options' && Array.isArray(data[key])) {
                control.value = data[key].join('\n');
            } else if (data[key] !== undefined && data[key] !== null) {
                control.value = data[key];
            }
        });
        const required = input(row, 'is_required');
        let previousRequired = required.checked;
        required.addEventListener('change', async () => {
            const next = required.checked;
            if (data.id) {
                required.checked = previousRequired;
                const confirmed = await confirmChange(
                    next ? 'Make Field Required?' : 'Make Field Optional?',
                    next ? 'Customers will need to provide this information for this service.' : 'Customers will be able to leave this information blank.',
                    next ? 'Make Required' : 'Make Optional',
                );
                if (!confirmed) return;
                required.checked = next;
            }
            previousRequired = next;
        });
        input(row, 'field_type').addEventListener('change', () => {
            const type = input(row, 'field_type').value;
            input(row, 'validation_type').value = ['email', 'phone', 'number'].includes(type) ? type : 'none';
            syncRow(row);
            validateRow(row);
        });
        input(row, 'validation_type').addEventListener('change', () => syncRow(row));
        row.addEventListener('input', () => { syncRow(row); validateRow(row, true); });
        row.addEventListener('change', () => { syncRow(row); validateRow(row, true); });
        row.querySelector('[data-remove-field]').addEventListener('click', () => {
            row.remove();
            sync();
            addButton.focus();
        });
        list.appendChild(row);
        showError();
        sync();
        if (focus) input(row, 'field_label').focus();
    }

    toggle.addEventListener('change', () => {
        if (toggle.checked && !rows().length) addField();
        showError();
        sync();
    });
    addButton.addEventListener('click', () => addField({}, true));
    document.getElementById('serviceModal').addEventListener('hidden.bs.modal', () => answerConfirmation(false));

    window.serviceReservedFields = {
        load(data = []) {
            answerConfirmation(false);
            list.replaceChildren();
            showError();
            const values = Array.isArray(data) ? data : Object.values(data || {});
            values.forEach(value => addField(value));
            sync();
        },
        validate(showInvalid = false) {
            if (pendingConfirmation) {
                confirmation.querySelector('[data-confirm-cancel]').focus();
                return false;
            }
            if (!toggle.checked) return true;
            if (!rows().length) {
                showError('Reserved is enabled. Please add at least one field or disable Reserved.');
                addButton.focus();
                return false;
            }
            showError();
            return rows().map(row => validateRow(row, showInvalid)).every(Boolean);
        },
    };
})();
