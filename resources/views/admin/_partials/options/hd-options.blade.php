@php
    $selected = $selected ?? '';
@endphp

<option value="h1" {{ $selected == 'h1' ? 'selected' : '' }}>&#10094;h1&#10095;</option>
<option value="h2" {{ $selected == 'h2' ? 'selected' : '' }}>&#10094;h2&#10095;</option>
<option value="h3" {{ $selected == 'h3' ? 'selected' : '' }}>&#10094;h3&#10095;</option>
<option value="h4" {{ $selected == 'h4' ? 'selected' : '' }}>&#10094;h4&#10095;</option>
<option value="h5" {{ $selected == 'h5' ? 'selected' : '' }}>&#10094;h5&#10095;</option>
<option value="h6" {{ $selected == 'h6' ? 'selected' : '' }}>&#10094;h6&#10095;</option>
