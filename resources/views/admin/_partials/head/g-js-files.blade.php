<script>
const theme = localStorage.getItem("theme") || "dark";
if (theme === "dark") document.documentElement.classList.add("dark");
window.APP_CURRENCY = @json(\App\Models\Currency::symbolForEvent($event ?? null));
</script>

<script src="{{ asset('javascript/bootstrap/bootstrap-pooper.js') }}" defer></script>
<script src="{{ asset('javascript/bootstrap/bootstrap.js') }}" defer></script>
<script src="{{ asset('javascript/global.js') }}" defer></script>
<script src="{{ asset('javascript/notification.js') }}" defer></script>
<script src="{{ asset('javascript/confirmation.js') }}" defer></script>
<script src="{{ asset('javascript/custom.js') }}" defer></script>
<script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>
