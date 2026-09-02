<option value="" disabled selected>Choose Ticket Type</option>
@php
    $currency = \App\Models\Currency::symbolForEvent($event ?? null);
@endphp
<option value="Garib Niwas">Garib Niwas - {{ $currency }}250</option>
<option value="VVIP">VVIP - {{ $currency }}500</option>
<option value="VIP" disabled>VIP - {{ $currency }}350 (Out of Stock)</option>
<option value="Premium">Premium - {{ $currency }}300</option>
