<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('head')
</head>
<body>
    @yield('body')

    @if (request()->boolean('not_found'))
        <div class="site-notification site-notification--error" role="alert" aria-live="assertive"
            data-site-notification>
            <i class="fa-solid fa-triangle-exclamation site-notification__icon" aria-hidden="true"></i>
            <span class="site-notification__message">Page not found. You have been redirected to the home page.</span>
            <button type="button" class="site-notification__close" aria-label="Close notification"
                data-site-notification-close>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const notification = document.querySelector('[data-site-notification]');

                if (!notification) {
                    return;
                }

                const closeNotification = function() {
                    notification.classList.remove('is-visible');
                    window.setTimeout(function() {
                        notification.remove();
                    }, 250);
                };

                window.requestAnimationFrame(function() {
                    notification.classList.add('is-visible');
                });

                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('not_found');
                window.history.replaceState(
                    {},
                    document.title,
                    `${cleanUrl.pathname}${cleanUrl.search}${cleanUrl.hash}`
                );

                notification.querySelector('[data-site-notification-close]')
                    ?.addEventListener('click', closeNotification);

                window.setTimeout(closeNotification, 6000);
            });
        </script>
    @endif
</body>
</html>
