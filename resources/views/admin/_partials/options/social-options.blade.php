@php
    $selected = $selected ?? '';
@endphp

<option value="" selected="" disabled="">Choose Platform</option>
@foreach(config('entities.social_options') as $key => $value)
    <option value="{{ $key }}" {{ $selected == $key ? 'selected' : '' }}>{{ $value['label'] }}</option>
@endforeach
