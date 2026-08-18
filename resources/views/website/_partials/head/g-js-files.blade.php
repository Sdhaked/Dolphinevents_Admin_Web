<script>
window.APP_CURRENCY = @json(\App\Models\Currency::symbolForEvent($event ?? null));
</script>
<script src="{{ asset('website/js/global.js') }}" defer></script>
