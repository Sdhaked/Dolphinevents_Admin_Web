@extends('layouts.admin')

@section('head')
    <title>Roles</title>
    <meta name="description" content="Manage admin roles and assigned permissions.">

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

            <h4 class="hd-lg">Roles</h4>

            <div>
                <h6 class="hd-sm">Total Result: <span>{{ $roles->total() }}</span></h6>

                <div class="dataTable-HD">
                    <div>
                        <a href="{{ route('admin.roles.create') }}" type="button" class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i> Create New
                        </a>
                    </div>

                    <form method="GET" style="flex-grow: 1; max-width: 480px;">
                        <input type="search" name="search" class="form-control" placeholder="Search"
                               value="{{ request('search') }}">
                        <span class="search-base">Search By: Name, Slug</span>
                    </form>
                </div>

                @include('admin.roles._partials.table', ['roles' => $roles])
            </div>
        </main>
    </section>
@endsection
