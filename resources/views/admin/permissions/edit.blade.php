@extends('layouts.admin')

@section('head')
    <title>Edit Permission</title>
    <meta name="description" content="Update permission details.">

    @include('admin._partials.head.g-links')
    @include('admin._partials.head.g-css-files')
    @include('admin._partials.head.g-js-files')
@endsection

@section('body')
    @include('admin._partials.preloader')
    @include('admin._partials.sidebar')
    @include('admin._partials.header')

    <section class="wrapper">
        <main class="dash-content">
            @include('admin._partials.breadcrumb')

            <h5 class="hd-lg">Edit Permission</h5>
            @include('admin.permissions._partials.form', [
                'action' => route('admin.permissions.update', $permission->id),
                'permission' => $permission,
            ])
        </main>
    </section>
@endsection
