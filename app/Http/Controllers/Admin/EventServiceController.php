<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventService;
use App\Models\EventServiceField;
use App\Models\Permission;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventServiceController extends Controller
{
    private const MANAGE_RESERVED_PERMISSION = 'event-services-manage-reserved';
    private const DELETE_RESERVED_PERMISSION = 'event-services-delete-reserved';

    public function index(Request $request)
    {
        $eventId = session('active_event_id');
        $event = Event::find($eventId);

        if (!$event) {
            return redirect()->route('admin.dashboard.index')->with('error', 'Please select an event first.');
        }

        $canManageReservedServices = $this->currentUserHasAnyPermission([self::MANAGE_RESERVED_PERMISSION]);
        $services = EventService::where('event_id', $eventId)
            ->when($canManageReservedServices, fn ($query) => $query->with('fields'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->latest('id')
            ->paginate(config('constants.pagination.per_page', 10));

        $ticketTypes = TicketType::where('event_id', $eventId)->orderBy('title')->get();
        $canDeleteReservedServices = $this->currentUserHasAnyPermission([
            self::DELETE_RESERVED_PERMISSION,
            self::MANAGE_RESERVED_PERMISSION,
        ]);
        $canDeleteEventServices = $this->currentUserHasAnyPermission([
            'event-services-delete-event-services',
            'event-services-manage-event-services',
            'ticket-types-manage-ticket-types',
        ]);
        $reservedFieldsForRetry = [];
        if ($canManageReservedServices) {
            $oldFields = $request->session()->getOldInput('reserved_fields');
            if (is_array($oldFields)) {
                $reservedFieldsForRetry = $oldFields;
            } elseif ($retryServiceId = $request->session()->getOldInput('_service_id')) {
                $retryService = EventService::where('event_id', $eventId)->with('fields')->find($retryServiceId);
                $reservedFieldsForRetry = $retryService?->fields->toArray() ?? [];
            }
        }

        return view('admin.event_services.index', compact(
            'event',
            'services',
            'ticketTypes',
            'canManageReservedServices',
            'canDeleteReservedServices',
            'canDeleteEventServices',
            'reservedFieldsForRetry'
        ));
    }

    public function store(Request $request)
    {
        $eventId = session('active_event_id');
        $request->merge(['_service_id' => null]);
        $canManageReserved = $this->authorizeReservedInput($request);
        $validated = $this->validatedData($request);
        $reservedFields = $canManageReserved ? $this->validatedReservedFields($request) : null;

        DB::transaction(function () use ($request, $validated, $eventId, $canManageReserved, $reservedFields) {
            $service = EventService::create([
                ...$validated,
                'event_id' => $eventId,
                'is_mandatory' => $request->boolean('is_mandatory'),
                'is_reserved' => $canManageReserved && $request->boolean('is_reserved'),
                'status' => $request->boolean('status'),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if ($reservedFields !== null) {
                $this->syncReservedFields($service, $reservedFields);
            }
        });

        return back()->with('success', 'Event service created successfully.');
    }

    public function update(Request $request, EventService $eventService)
    {
        $this->abortIfWrongEvent($eventService);
        $request->merge(['_service_id' => $eventService->id]);
        $canManageReserved = $this->authorizeReservedInput($request);
        $validated = $this->validatedData($request);
        $reservedFields = $canManageReserved ? $this->validatedReservedFields($request, $eventService) : null;

        DB::transaction(function () use ($request, $eventService, $validated, $canManageReserved, $reservedFields) {
            $eventService->update([
                ...$validated,
                'is_mandatory' => $request->boolean('is_mandatory'),
                'is_reserved' => $canManageReserved ? $request->boolean('is_reserved') : $eventService->is_reserved,
                'status' => $request->boolean('status'),
                'updated_by' => Auth::id(),
            ]);

            if ($reservedFields !== null) {
                $this->syncReservedFields($eventService, $reservedFields);
            }
        });

        return back()->with('success', 'Event service updated successfully.');
    }

    public function destroy(EventService $eventService)
    {
        $this->abortIfWrongEvent($eventService);

        if ($eventService->is_reserved && !$this->currentUserHasAnyPermission([
            self::DELETE_RESERVED_PERMISSION,
            self::MANAGE_RESERVED_PERMISSION,
        ])) {
            abort(403, 'You do not have permission to delete Reserved services.');
        }

        $eventService->delete();

        return back()->with('success', 'Event service deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        $eventId = (int) session('active_event_id');
        $request->merge([
            'name' => trim((string) $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'available_quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'max_buy_limit' => ['required', 'integer', 'min:1', 'lte:available_quantity', 'max:999999'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'applicable_ticket_type_ids' => ['nullable', 'array'],
            'applicable_ticket_type_ids.*' => [
                'integer',
                Rule::exists('ticket_types', 'id')->where(fn ($query) => $query->where('event_id', $eventId)),
            ],
        ], [
            'available_quantity.min' => 'Available quantity must be at least 1.',
            'max_buy_limit.lte' => 'Max buy limit cannot be greater than available quantity.',
            'price.regex' => 'Price can have a maximum of 2 decimal places.',
            'applicable_ticket_type_ids.*.exists' => 'Selected ticket type is not available for the current event.',
        ], [
            'name' => 'service name',
            'available_quantity' => 'available quantity',
            'max_buy_limit' => 'max buy limit',
            'applicable_ticket_type_ids.*' => 'ticket type',
        ]);

        $validated['applicable_ticket_type_ids'] = collect($validated['applicable_ticket_type_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $validated;
    }

    private function authorizeReservedInput(Request $request): bool
    {
        $allowed = $this->currentUserHasAnyPermission([self::MANAGE_RESERVED_PERMISSION]);

        if (!$allowed && ($request->exists('is_reserved') || $request->exists('reserved_fields'))) {
            abort(403, 'You do not have permission to configure Reserved services.');
        }

        return $allowed;
    }

    private function validatedReservedFields(Request $request, ?EventService $service = null): ?array
    {
        $request->validate(['is_reserved' => ['sometimes', 'boolean']]);

        // Disabling Reserved retains the saved configuration for later use.
        if (!$request->boolean('is_reserved')) {
            return null;
        }

        $validator = Validator::make($request->all(), [
            'reserved_fields' => ['required', 'array', 'min:1', 'max:50'],
            'reserved_fields.*' => ['array:id,field_label,field_type,is_required,validation_type,validation_pattern,placeholder,help_text,error_message,min_value,max_value,max_length,options'],
            'reserved_fields.*.id' => [
                'nullable', 'integer', 'distinct',
                Rule::exists('event_service_fields', 'id')->where(fn ($query) => $query
                    ->where('event_service_id', $service?->id ?? 0)->whereNull('deleted_at')),
            ],
            'reserved_fields.*.field_label' => ['required', 'string', 'max:255'],
            'reserved_fields.*.field_type' => ['required', Rule::in(EventServiceField::FIELD_TYPES)],
            'reserved_fields.*.is_required' => ['sometimes', 'boolean'],
            'reserved_fields.*.validation_type' => ['required', Rule::in(EventServiceField::VALIDATION_TYPES)],
            'reserved_fields.*.validation_pattern' => ['nullable', 'string', 'max:2000'],
            'reserved_fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'reserved_fields.*.help_text' => ['nullable', 'string', 'max:1000'],
            'reserved_fields.*.error_message' => ['nullable', 'string', 'max:1000'],
            'reserved_fields.*.min_value' => ['nullable', 'numeric', 'between:-99999999999,99999999999'],
            'reserved_fields.*.max_value' => ['nullable', 'numeric', 'between:-99999999999,99999999999'],
            'reserved_fields.*.max_length' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'reserved_fields.*.options' => ['nullable', 'string', 'max:25600'],
        ], [
            'reserved_fields.required' => 'Reserved is enabled. Please add at least one additional information field or disable Reserved.',
            'reserved_fields.min' => 'Please add at least one additional information field.',
            'reserved_fields.*.id.exists' => 'This field does not belong to the service being edited.',
            'reserved_fields.*.id.distinct' => 'Each saved field can only appear once.',
            'reserved_fields.*.field_label.required' => 'Enter a field label.',
            'reserved_fields.*.field_type.required' => 'Select a field type.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($request->input('reserved_fields', []) as $index => $field) {
                $prefix = "reserved_fields.{$index}";

                if (trim($field['field_label']) === '') {
                    $validator->errors()->add("{$prefix}.field_label", 'Enter a field label.');
                }

                if (($field['validation_type'] ?? 'none') === 'custom') {
                    $pattern = (string) ($field['validation_pattern'] ?? '');
                    // A delimiter absent from the source preserves escaped literal characters.
                    $delimiter = collect(['~', '#', '%', '!', '@', ';', '/', '`', "\x01"])
                        ->first(fn ($candidate) => !str_contains($pattern, $candidate));
                    if (trim($pattern) === '' || $delimiter === null
                        || @preg_match($delimiter.$pattern.$delimiter.'u', '') === false) {
                        $validator->errors()->add("{$prefix}.validation_pattern", 'Enter a valid custom validation pattern without regex delimiters.');
                    }
                }

                if (isset($field['min_value'], $field['max_value'])
                    && (float) $field['min_value'] > (float) $field['max_value']) {
                    $validator->errors()->add("{$prefix}.max_value", 'Maximum must be greater than or equal to minimum.');
                }

                if (in_array($field['field_type'], ['dropdown', 'radio'], true)) {
                    $options = $this->fieldOptions($field['options'] ?? '');
                    if (empty($options) || count($options) > 100
                        || count($options) !== count(array_unique($options))
                        || collect($options)->contains(fn ($option) => mb_strlen($option) > 255)) {
                        $validator->errors()->add("{$prefix}.options", 'Enter 1 to 100 unique options, one per line, with at most 255 characters each.');
                    }
                }
            }
        });

        return array_values($validator->validate()['reserved_fields']);
    }

    private function fieldOptions(string $options): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $options)), fn ($option) => $option !== ''));
    }

    private function syncReservedFields(EventService $service, array $fields): void
    {
        $savedFields = $service->fields()->lockForUpdate()->get()->keyBy('id');
        $usedKeys = $service->fields()->withTrashed()->pluck('field_key')->all();
        $retainedIds = [];

        foreach ($fields as $position => $data) {
            $field = !empty($data['id']) ? $savedFields->get((int) $data['id']) : null;
            abort_if(!empty($data['id']) && !$field, 422, 'A configured field changed. Reload the service and try again.');

            $attributes = [
                'field_label' => trim($data['field_label']),
                'field_type' => $data['field_type'],
                'is_required' => (bool) ($data['is_required'] ?? false),
                'validation_type' => $data['validation_type'],
                'validation_pattern' => $data['validation_type'] === 'custom' ? $data['validation_pattern'] : null,
                'placeholder' => $data['placeholder'] ?? null,
                'help_text' => $data['help_text'] ?? null,
                'error_message' => $data['error_message'] ?? null,
                'min_value' => $data['min_value'] ?? null,
                'max_value' => $data['max_value'] ?? null,
                'max_length' => $data['max_length'] ?? null,
                'options' => in_array($data['field_type'], ['dropdown', 'radio'], true) ? $this->fieldOptions($data['options'] ?? '') : null,
                'sort_order' => $position,
            ];

            if ($field) {
                $field->update($attributes);
            } else {
                $baseKey = Str::limit(Str::slug($attributes['field_label'], '_'), 220, '') ?: 'field';
                $fieldKey = $baseKey;
                $suffix = 2;
                while (in_array($fieldKey, $usedKeys, true)) {
                    $fieldKey = $baseKey.'_'.($suffix++);
                }
                $usedKeys[] = $fieldKey;
                $field = $service->fields()->create([...$attributes, 'field_key' => $fieldKey]);
            }

            $retainedIds[] = $field->id;
        }

        $service->fields()->whereNotIn('id', $retainedIds)->delete();
    }

    private function abortIfWrongEvent(EventService $eventService): void
    {
        abort_unless((int) $eventService->event_id === (int) session('active_event_id'), 404);
    }

    private function currentUserHasAnyPermission(array $permissions): bool
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) {
            return true;
        }

        $user = Auth::user();

        if (!$user?->role) {
            return false;
        }

        return Permission::query()
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', (int) $user->role)
            ->whereIn('permissions.slug', $permissions)
            ->exists();
    }
}
