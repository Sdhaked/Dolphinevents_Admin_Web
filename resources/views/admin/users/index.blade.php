@extends('layouts.admin')

@section('head')
    <title>Users</title>
    <meta name="description" content="Manage admin users.">

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

            <h4 class="hd-lg">Users</h4>

            <div>
                <h6 class="hd-sm">Total Result: <span>{{ $users->total() }}</span></h6>

                <div class="dataTable-HD">
                    <div>
                        <a href="{{ route('admin.users.create') }}" type="button" class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i> Create New
                        </a>
                    </div>

                    <form method="GET" style="flex-grow: 1; max-width: 480px;">
                        <input type="search" name="search" class="form-control" placeholder="Search"
                               value="{{ request('search') }}">
                        <span class="search-base">Search By: Name, Username, Email, Mobile</span>
                    </form>
                </div>

                @include('admin.users._partials.table', ['users' => $users])
            </div>
        </main>
    </section>
@endsection
