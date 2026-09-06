@if(!empty($eventServices) && $eventServices->count())
    <div class="style-box" id="eventServiceSection" style="display: none;">
        <h6 class="hd-sm mb-2">Additional Services</h6>
        <div class="grid-1 gap-card">
            @foreach($eventServices as $service)
                @php
                    $serviceMaxQty = min(max(1, (int) $service->max_buy_limit), 20);
                    $applicableTicketTypes = array_map('intval', $service->applicable_ticket_type_ids ?: []);
                @endphp
                <div class="style-box admin-event-service-row"
                    data-service-id="{{ $service->id }}"
                    data-ticket-types='@json($applicableTicketTypes)'
                    data-reserved="{{ $service->is_reserved ? 1 : 0 }}"
                    style="display: none;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-card">
                        <div>
                            <h6 class="hd-sm mb-1">{{ $service->name }}</h6>
                            <p class="mb-0">
                                {{ $currency }}{{ number_format((float) $service->price, 2) }}/-
                                @if($service->is_mandatory)
                                    <span class="tag">Mandatory</span>
                                @endif
                                @if($service->is_reserved)
                                    <span class="tag">Reserved</span>
                                @endif
                            </p>
                        </div>
                        <div class="form-floating" style="min-width: 140px;">
                            <select class="form-select admin-event-service-qty" data-id="{{ $service->id }}"
                                data-mandatory="{{ $service->is_mandatory ? 1 : 0 }}" disabled>
                                @for($i = $service->is_mandatory ? 1 : 0; $i <= $serviceMaxQty; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                            <label>Qty</label>
                        </div>
                    </div>

                    @if($service->is_reserved && $service->fields->isNotEmpty())
                        <div class="admin-event-service-fields mt-3" style="display: none;"></div>
                        <template class="admin-event-service-unit-template">
                            <fieldset class="style-box admin-event-service-unit mb-2">
                                <legend class="hd-sm admin-event-service-unit-title"></legend>
                                <div class="grid-1 gap-card">
                                    @foreach($service->fields as $field)
                                        @php
                                            $inputType = match ($field->field_type) {
                                                'number' => 'number',
                                                'email' => 'email',
                                                'phone' => 'tel',
                                                'date' => 'date',
                                                'time' => 'time',
                                                'datetime' => 'datetime-local',
                                                default => $field->validation_type === 'email' ? 'email'
                                                    : ($field->validation_type === 'phone' ? 'tel'
                                                    : ($field->validation_type === 'number' ? 'number'
                                                    : ($field->validation_type === 'url' ? 'url' : 'text'))),
                                            };
                                        @endphp
                                        <div class="admin-event-service-field-group"
                                            data-field-id="{{ $field->id }}"
                                            data-field-key="{{ $field->field_key }}"
                                            data-field-type="{{ $field->field_type }}"
                                            data-required="{{ $field->is_required ? 1 : 0 }}"
                                            data-error-message="{{ $field->error_message }}">
                                            <label class="form-label">
                                                {{ $field->field_label }}@if($field->is_required)<span class="text-danger"> *</span>@endif
                                            </label>

                                            @if($field->field_type === 'textarea')
                                                <textarea class="form-control admin-event-service-field"
                                                    data-field-id="{{ $field->id }}"
                                                    placeholder="{{ $field->placeholder }}"
                                                    @if($field->max_length) maxlength="{{ $field->max_length }}" @endif
                                                    @if($field->validation_type === 'custom' && filled($field->validation_pattern)) pattern="{{ $field->validation_pattern }}" @endif></textarea>
                                            @elseif($field->field_type === 'dropdown')
                                                <select class="form-select admin-event-service-field" data-field-id="{{ $field->id }}">
                                                    <option value="">Select {{ $field->field_label }}</option>
                                                    @foreach($field->options ?: [] as $option)
                                                        <option value="{{ $option }}">{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($field->field_type === 'radio')
                                                <div class="d-flex flex-wrap gap-card">
                                                    @foreach($field->options ?: [] as $option)
                                                        <label class="form-check">
                                                            <input class="form-check-input admin-event-service-field" type="radio"
                                                                data-field-id="{{ $field->id }}" value="{{ $option }}">
                                                            <span class="form-check-label">{{ $option }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @elseif($field->field_type === 'checkbox')
                                                <label class="form-check">
                                                    <input class="form-check-input admin-event-service-field" type="checkbox"
                                                        data-field-id="{{ $field->id }}" value="1">
                                                    <span class="form-check-label">Yes</span>
                                                </label>
                                            @else
                                                <input class="form-control admin-event-service-field" type="{{ $inputType }}"
                                                    data-field-id="{{ $field->id }}"
                                                    placeholder="{{ $field->placeholder }}"
                                                    @if($field->min_value !== null && $inputType === 'number') min="{{ $field->min_value }}" @endif
                                                    @if($field->max_value !== null && $inputType === 'number') max="{{ $field->max_value }}" @endif
                                                    @if($field->max_length && !in_array($inputType, ['number', 'date', 'time', 'datetime-local'])) maxlength="{{ $field->max_length }}" @endif
                                                    @if($field->validation_type === 'custom' && filled($field->validation_pattern)) pattern="{{ $field->validation_pattern }}" @endif>
                                            @endif

                                            @if(filled($field->help_text))
                                                <small class="text-muted d-block mt-1">{{ $field->help_text }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        </template>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
