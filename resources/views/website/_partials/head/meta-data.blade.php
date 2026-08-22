@php
    $rawMetaData = trim((string) ($metaData ?? ''));
    $fallbackTitle = $fallbackTitle ?? 'Dolphinevent';
    $decodedMetaData = json_decode($rawMetaData, true);

    if (json_last_error() === JSON_ERROR_NONE && is_string($decodedMetaData)) {
        $rawMetaData = trim($decodedMetaData);
    }

    $hasValidHeadMarkup = $rawMetaData !== ''
        && preg_match('/^<(title|meta|link|script|style)\b/i', $rawMetaData);
@endphp

@if ($hasValidHeadMarkup)
    {!! $rawMetaData !!}
@else
    <title>{{ $fallbackTitle }}</title>
@endif
