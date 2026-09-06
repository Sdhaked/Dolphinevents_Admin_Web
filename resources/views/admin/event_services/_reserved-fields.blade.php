<section id="reservedFieldsSection" class="border rounded p-3" aria-labelledby="reservedFieldsHeading" hidden>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h6 id="reservedFieldsHeading" class="m-0">Additional Information Fields</h6>
        <button type="button" id="addReservedField" class="btn-sm btn-sec">
            <i class="fa-solid fa-plus i-mr" aria-hidden="true"></i> Add Field
        </button>
    </div>
    <p class="small mb-3">Configure the additional information for this Reserved service.</p>
    @if ($errors->has('reserved_fields') || $errors->has('reserved_fields.*'))
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->getMessages() as $key => $messages)
                @if ($key === 'reserved_fields' || str_starts_with($key, 'reserved_fields.'))
                    @foreach ($messages as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                @endif
            @endforeach
        </div>
    @endif
    <div id="reservedFieldsError" class="text-danger small mb-2" role="alert" hidden></div>
    <div id="reservedFieldsConfirmation" class="alert alert-warning" role="alert" hidden>
        <strong data-confirm-title></strong>
        <p class="mb-2" data-confirm-message></p>
        <div class="d-flex gap-2">
            <button type="button" class="btn-sm btn-sec" data-confirm-cancel>Cancel</button>
            <button type="button" class="btn-sm btn-danger" data-confirm-accept>Confirm</button>
        </div>
    </div>
    <div id="reservedFieldsList" class="d-grid gap-3"></div>
    <p id="reservedFieldsEmpty" class="small mb-0">No fields configured. Select Add Field to get started.</p>
</section>

<template id="reservedFieldTemplate">
    <div class="reserved-field border rounded p-3">
        <input type="hidden" data-property="id">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong data-field-heading>Field</strong>
            <button type="button" class="btn-sm btn-outline-danger" data-remove-field>
                <i class="fa-solid fa-trash i-mr" aria-hidden="true"></i> Remove
            </button>
        </div>
        <div class="grid-2 grid-sm-1 gap-card mb-3">
            <div>
                <label class="mb-1" data-label-for="field_label">Field Label *</label>
                <input type="text" class="form-control" data-property="field_label" maxlength="255" required placeholder="e.g. Required Detail">
                <div class="invalid-feedback">Enter a field label.</div>
            </div>
            <div>
                <label class="mb-1" data-label-for="field_type">Field Type *</label>
                <select class="form-select" data-property="field_type" required>
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                    <option value="date">Date</option>
                    <option value="time">Time</option>
                    <option value="datetime">Date &amp; Time</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="radio">Radio</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="textarea">Textarea</option>
                </select>
            </div>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" data-property="is_required" value="1">
            <label class="form-check-label" data-label-for="is_required">Required</label>
        </div>
        <div data-options-group class="mb-3" hidden>
            <label class="mb-1" data-label-for="options">Options *</label>
            <textarea class="form-control" data-property="options" rows="3" maxlength="25600" placeholder="One option per line"></textarea>
            <div class="invalid-feedback">Enter at least one option, one per line.</div>
            <small>One option per line, for example Vegetarian, Non-Vegetarian, Vegan.</small>
        </div>
        <details>
            <summary class="mb-3">Validation &amp; help text</summary>
            <div class="d-grid gap-3">
                <div>
                    <label class="mb-1" data-label-for="validation_type">Validation Type</label>
                    <select class="form-select" data-property="validation_type">
                        <option value="none">None</option>
                        <option value="email">Email</option>
                        <option value="phone">Phone Number (10 digits)</option>
                        <option value="number">Number</option>
                        <option value="url">URL</option>
                        <option value="vehicle_number">Vehicle Number</option>
                        <option value="custom">Custom Pattern</option>
                    </select>
                    <small data-validation-help></small>
                </div>
                <div data-pattern-group hidden>
                    <label class="mb-1" data-label-for="validation_pattern">Custom Pattern *</label>
                    <input type="text" class="form-control" data-property="validation_pattern" maxlength="2000" placeholder="e.g. ^[A-Z0-9]+$">
                    <div class="invalid-feedback">Enter a valid validation pattern.</div>
                </div>
                <div data-number-group class="grid-2 grid-sm-1 gap-card" hidden>
                    <div>
                        <label class="mb-1" data-label-for="min_value">Minimum</label>
                        <input type="number" class="form-control" data-property="min_value" step="any">
                    </div>
                    <div>
                        <label class="mb-1" data-label-for="max_value">Maximum</label>
                        <input type="number" class="form-control" data-property="max_value" step="any">
                        <div class="invalid-feedback">Maximum must be at least Minimum.</div>
                    </div>
                </div>
                <div data-length-group>
                    <label class="mb-1" data-label-for="max_length">Max Length</label>
                    <input type="number" class="form-control" data-property="max_length" min="1" max="10000" step="1">
                    <div class="invalid-feedback">Enter a whole number from 1 to 10000.</div>
                </div>
                <div>
                    <label class="mb-1" data-label-for="placeholder">Placeholder</label>
                    <input type="text" class="form-control" data-property="placeholder" maxlength="255">
                </div>
                <div>
                    <label class="mb-1" data-label-for="help_text">Help Text</label>
                    <textarea class="form-control" data-property="help_text" maxlength="1000" rows="2"></textarea>
                </div>
                <div>
                    <label class="mb-1" data-label-for="error_message">Validation Error Message</label>
                    <input type="text" class="form-control" data-property="error_message" maxlength="1000" placeholder="e.g. Please enter a valid value.">
                </div>
            </div>
        </details>
    </div>
</template>
