<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('head')
</head>

<body>
    @yield('body')

    @include('admin._partials.media-delete-modal')

    <!-- Global Session Messages -->
    @if (session('success'))
        <script>
            window.addEventListener('load', function() {
                createNotification("success", "{{ session('success') }}", "");
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            window.addEventListener('load', function() {
                createNotification("error", "{{ session('error') }}", "");
            });
        </script>
    @endif

    @if (session('warning'))
        <script>
            window.addEventListener('load', function() {
                createNotification("warning", "{{ session('warning') }}", "");
            });
        </script>
    @endif

    @if (session('info'))
        <script>
            window.addEventListener('load', function() {
                createNotification("info", "{{ session('info') }}", "");
            });
        </script>
    @endif

    @yield('custom-script')
</body>

</html>
