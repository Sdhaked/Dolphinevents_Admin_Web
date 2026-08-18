@php
    $ageGroups = old('age_group_label')
        ? collect(old('age_group_label'))->map(function ($label, $index) {
            return [
                'label' => $label,
                'price' => old('age_group_price.' . $index),
                'total_tickets' => old('age_group_total_tickets.' . $index),
                'max_quantity_per_booking' => old('age_group_max_quantity.' . $index, 20),
                'is_compulsory' => in_array((string) $index, array_map('strval', old('age_group_compulsory', [])), true),
            ];
        })
        : collect($ticket?->ageGroups ?? []);
@endphp

<div class="style-box" id="ageGroupBox">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-card mb-3">
        <button type="button" class="check-btn">
            <input class="form-check-input" name="enable_age_group" type="checkbox" value="1"
                id="enable-age-group" {{ old('enable_age_group', $ticket?->enable_age_group ?? false) ? 'checked' : '' }}>
            <label for="enable-age-group"> Enable Age Group Pricing</label>
        </button>

        <button type="button" class="btn-xs btn-sec" id="addAgeGroupRow">
            <i class="fa-solid fa-plus i-mr"></i> Add Age Slab
        </button>
    </div>

    <div class="table-responsive">
        <table class="table mob-view">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Price</th>
                    <th>Total Qty</th>
                    <th>Max/Booking</th>
                    <th>Compulsory</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="ageGroupRows">
                @foreach ($ageGroups as $index => $ageGroup)
                    <tr>
                        <td>
                            <input type="text" name="age_group_label[]" class="form-control"
                                value="{{ is_array($ageGroup) ? $ageGroup['label'] : $ageGroup->label }}"
                                placeholder="Adult / Age 18-80">
                        </td>
                        <td>
                            <input type="number" name="age_group_price[]" class="form-control" min="0" step="0.01"
                                value="{{ is_array($ageGroup) ? $ageGroup['price'] : $ageGroup->price }}">
                        </td>
                        <td>
                            <input type="number" name="age_group_total_tickets[]" class="form-control" min="0"
                                value="{{ is_array($ageGroup) ? $ageGroup['total_tickets'] : $ageGroup->total_tickets }}">
                        </td>
                        <td>
                            <input type="number" name="age_group_max_quantity[]" class="form-control" min="1" max="20"
                                value="{{ is_array($ageGroup) ? $ageGroup['max_quantity_per_booking'] : $ageGroup->max_quantity_per_booking }}">
                        </td>
                        <td>
                            <input type="checkbox" name="age_group_compulsory[]" value="{{ $index }}" class="form-check-input age-group-compulsory"
                                @checked(is_array($ageGroup) ? $ageGroup['is_compulsory'] : $ageGroup->is_compulsory)>
                        </td>
                        <td>
                            <button type="button" class="action-btn delete remove-age-group-row">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rows = document.getElementById('ageGroupRows');
            const addBtn = document.getElementById('addAgeGroupRow');

            if (!rows || !addBtn) return;

            function rowTemplate(index) {
                return `
                    <tr>
                        <td><input type="text" name="age_group_label[]" class="form-control" placeholder="Adult / Age 18-80"></td>
                        <td><input type="number" name="age_group_price[]" class="form-control" min="0" step="0.01"></td>
                        <td><input type="number" name="age_group_total_tickets[]" class="form-control" min="0"></td>
                        <td><input type="number" name="age_group_max_quantity[]" class="form-control" min="1" max="20" value="20"></td>
                        <td><input type="checkbox" name="age_group_compulsory[]" value="${index}" class="form-check-input age-group-compulsory"></td>
                        <td>
                            <button type="button" class="action-btn delete remove-age-group-row">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }

            function reindexRows() {
                rows.querySelectorAll('tr').forEach((row, index) => {
                    const checkbox = row.querySelector('.age-group-compulsory');
                    if (checkbox) checkbox.value = index;
                });
            }

            addBtn.addEventListener('click', function () {
                rows.insertAdjacentHTML('beforeend', rowTemplate(rows.children.length));
                reindexRows();
            });

            rows.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-age-group-row');
                if (!button) return;

                button.closest('tr')?.remove();
                reindexRows();
            });
        });
    </script>
@endonce
