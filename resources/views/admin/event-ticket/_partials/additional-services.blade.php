@if(!empty($eventServices) && $eventServices->count())
    <div class="style-box" id="eventServiceSection" style="display: none;">
        <h6 class="hd-sm mb-2">Additional Services</h6>
        <div class="grid-1 gap-card">
            @foreach($eventServices as $service)
                @php
                    $serviceMaxQty = min(max(1, (int) $service->max_buy_limit), 20);
                    $applicableTicketTypes = array_map('intval', $service->applicable_ticket_type_ids ?: []);
                @endphp
                <div class="style-box admin-event-service-row" data-ticket-types='@json($applicableTicketTypes)' style="display: none;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-card">
                        <div>
                            <h6 class="hd-sm mb-1">{{ $service->name }}</h6>
                            <p class="mb-0">
                                {{ $currency }}{{ number_format((float) $service->price, 2) }}/-
                                @if($service->is_mandatory)
                                    <span class="tag">Mandatory</span>
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
                </div>
            @endforeach
        </div>
    </div>
@endif
