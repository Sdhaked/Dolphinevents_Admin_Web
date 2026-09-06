<?php

namespace App\Services;

use App\Models\EventService;
use App\Models\EventServiceField;
use App\Models\TicketCounterService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EventServiceFieldResponseService
{
    /**
     * Validate and snapshot Reserved-service responses for every purchased unit.
     *
     * Expected input: [zeroBasedUnitIndex => [eventServiceFieldId => value]].
     * For a one-unit purchase, a flat [eventServiceFieldId => value] map is also accepted.
     */
    public function validateAndNormalize(
        EventService $service,
        int $quantity,
        mixed $submittedValues,
        string $errorPrefix = 'service_items',
        bool $strict = true
    ): array {
        if (!$service->is_reserved || $quantity <= 0) {
            return [];
        }

        $fields = $service->relationLoaded('fields')
            ? $service->fields
            : $service->fields()->get();

        if ($fields->isEmpty()) {
            throw ValidationException::withMessages([
                $errorPrefix => "{$service->name} does not have any additional information fields configured.",
            ]);
        }

        $unitValues = $this->normalizeUnits($submittedValues, $quantity, $fields->pluck('id')->all());
        $allowedIds = $fields->pluck('id')->map(fn ($id) => (string) $id)->all();
        $responses = [];

        foreach ($unitValues as $unitIndex => $values) {
            $values = is_array($values) ? $values : [];

            foreach (array_keys($values) as $submittedFieldId) {
                if (!in_array((string) $submittedFieldId, $allowedIds, true)) {
                    throw ValidationException::withMessages([
                        "{$errorPrefix}.field_values.{$unitIndex}.{$submittedFieldId}" => 'Invalid additional information field.',
                    ]);
                }
            }

            foreach ($fields as $field) {
                $path = "{$errorPrefix}.field_values.{$unitIndex}.{$field->id}";
                $value = Arr::get($values, (string) $field->id);
                $normalized = $this->validateValue($field, $value, $path, $strict);

                $responses[] = [
                    'event_service_field_id' => $field->id,
                    'unit_number' => $unitIndex + 1,
                    'field_label' => $field->field_label,
                    'field_key' => $field->field_key,
                    'field_type' => $field->field_type,
                    'value' => $normalized,
                ];
            }
        }

        return $responses;
    }

    public function sync(TicketCounterService $bookingService, array $responses): void
    {
        $bookingService->fieldValues()->delete();

        if ($responses !== []) {
            $bookingService->fieldValues()->createMany($responses);
        }
    }

    private function normalizeUnits(mixed $submittedValues, int $quantity, array $fieldIds): array
    {
        $submittedValues = is_array($submittedValues) ? $submittedValues : [];
        $fieldIdStrings = array_map('strval', $fieldIds);

        $isFlatSingleUnit = $quantity === 1
            && collect(array_keys($submittedValues))->every(
                fn ($key) => in_array((string) $key, $fieldIdStrings, true)
            );

        if ($isFlatSingleUnit) {
            $submittedValues = [$submittedValues];
        }

        return collect(range(0, $quantity - 1))
            ->map(fn ($unitIndex) => $submittedValues[$unitIndex] ?? [])
            ->all();
    }

    private function validateValue(EventServiceField $field, mixed $value, string $path, bool $strict): mixed
    {
        if ($field->field_type === 'checkbox') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            if ($strict && $field->is_required && !$value) {
                $this->fail($path, $field, "Please select {$field->field_label}.");
            }

            return $value;
        }

        if (is_array($value) || is_object($value)) {
            $this->fail($path, $field);
        }

        $value = is_string($value) ? trim($value) : $value;
        $isEmpty = $value === null || $value === '';

        if ($isEmpty) {
            if ($strict && $field->is_required) {
                $this->fail($path, $field, "{$field->field_label} is required.");
            }

            return null;
        }

        if (!is_scalar($value)) {
            $this->fail($path, $field);
        }

        $stringValue = (string) $value;

        if ($field->max_length && mb_strlen($stringValue) > (int) $field->max_length) {
            $this->fail($path, $field, "{$field->field_label} may not be greater than {$field->max_length} characters.");
        }

        if ($field->field_type === 'number' || $field->validation_type === 'number') {
            if (!is_numeric($value)) {
                $this->fail($path, $field, "{$field->field_label} must be a number.");
            }

            $numericValue = (float) $value;
            if ($field->min_value !== null && $numericValue < (float) $field->min_value) {
                $this->fail($path, $field, "{$field->field_label} must be at least {$field->min_value}.");
            }
            if ($field->max_value !== null && $numericValue > (float) $field->max_value) {
                $this->fail($path, $field, "{$field->field_label} may not be greater than {$field->max_value}.");
            }
        }

        if (in_array($field->field_type, ['dropdown', 'radio'], true)
            && !in_array($stringValue, $field->options ?: [], true)) {
            $this->fail($path, $field, "Please select a valid {$field->field_label}.");
        }

        $valid = match ($field->validation_type) {
            'email' => filter_var($stringValue, FILTER_VALIDATE_EMAIL) !== false,
            'phone' => preg_match('/^[0-9]{10}$/', $stringValue) === 1,
            'url' => filter_var($stringValue, FILTER_VALIDATE_URL) !== false,
            'vehicle_number' => preg_match('/^[A-Z]{2}[0-9]{2}[A-Z]{1,3}[0-9]{4}$/', strtoupper(preg_replace('/[\s-]+/', '', $stringValue))) === 1,
            'custom' => $this->matchesCustomPattern($stringValue, (string) $field->validation_pattern),
            default => true,
        };

        if (!$valid) {
            $this->fail($path, $field);
        }

        if (!$this->validFieldTypeValue($field->field_type, $stringValue)) {
            $this->fail($path, $field);
        }

        return $field->field_type === 'number' ? (float) $value : $stringValue;
    }

    private function validFieldTypeValue(string $fieldType, string $value): bool
    {
        return match ($fieldType) {
            'number' => is_numeric($value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'phone' => preg_match('/^[0-9]{10}$/', $value) === 1,
            'date' => $this->matchesDateFormat($value, 'Y-m-d'),
            'time' => $this->matchesDateFormat($value, 'H:i') || $this->matchesDateFormat($value, 'H:i:s'),
            'datetime' => $this->matchesDateFormat($value, 'Y-m-d\\TH:i')
                || $this->matchesDateFormat($value, 'Y-m-d H:i:s'),
            default => true,
        };
    }

    private function matchesDateFormat(string $value, string $format): bool
    {
        try {
            $date = Carbon::createFromFormat($format, $value);

            return $date !== false && $date->format($format) === $value;
        } catch (\Throwable) {
            return false;
        }
    }

    private function matchesCustomPattern(string $value, string $pattern): bool
    {
        $delimiter = collect(['~', '#', '%', '!', '@', ';', '/', '`', "\x01"])
            ->first(fn ($candidate) => !str_contains($pattern, $candidate));

        return $delimiter !== null && @preg_match($delimiter.$pattern.$delimiter.'u', $value) === 1;
    }

    private function fail(string $path, EventServiceField $field, ?string $fallback = null): never
    {
        throw ValidationException::withMessages([
            $path => filled($field->error_message)
                ? $field->error_message
                : ($fallback ?: "Please enter a valid {$field->field_label}."),
        ]);
    }
}
