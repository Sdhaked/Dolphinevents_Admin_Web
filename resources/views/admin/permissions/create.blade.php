@extends('layouts.admin')

@section('head')
    <title>Create Permission</title>
    <meta name="description" content="Create a new permission.">

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

            <h5 class="hd-lg">Create Permission</h5>
            @include('admin.permissions._partials.form', [
                'action' => route('admin.permissions.store'),
            ])
        </main>
    </section>
@endsection
