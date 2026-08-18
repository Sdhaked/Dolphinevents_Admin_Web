<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title')</title>
    <meta name="description" content="Set new password for your account.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')
    <link rel="stylesheet" href="{{ asset('style/page/authenticate.css') }}">

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
</head>

<body>
<!-- PRELOADER -->
@include('admin._partials.preloader')

@yield('body')

</body>

</html>