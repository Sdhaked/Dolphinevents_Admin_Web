@extends('layouts.admin')

@section('head')
    <title>Sponsors</title>
    <meta name="description" content="lorem hdihf ffhefef e9fje9fje9fef jefje9 fefef.">

    <!----======== Head Files ======== -->
    @include('admin._partials.head.g-links')

    <!----======== CSS ======== -->
    @include('admin._partials.head.g-css-files')

    <!----======== JS ======== -->
    @include('admin._partials.head.g-js-files')
    <script src="{{ asset('javascript/pagination.js') }}" defer></script>
@endsection

@section('body')
    <!-- PRELOADER -->
    @include('admin._partials.preloader')

    <!-- SideBar (Nav Items) -->
    @include('admin._partials.sidebar')

    <!-- TOP HEADER -->
    @include('admin._partials.header')

    <!-- MAIN CONTENT 🥗 -->
    <section class="wrapper">
        <main class="dash-content">
            <!-- Breadcrumb -->
            @include('admin._partials.breadcrumb')

            <div>
                <h4 class="hd-lg">Sponsors</h4>

                <!-- Data table Head -->
                <div class="dataTable-HD">
                    <div>
                        <button data-bs-toggle="modal" data-bs-target="#infoslid" type="button" class="btn-sm btn-sec">
                            <i class="fa-solid fa-plus i-mr"></i>
                            Add New</button>
                    </div>

                    <div style="flex-grow: 1; max-width: 480px;">
                        <input type="search" class="form-control" placeholder="Search" />
                        <span class="search-base">Search By: Attached Link</span>
                    </div>

                </div>


                <!-- Data Table -->
                <div class="table-responsive mt-4">
                    <table class="table mob-view">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th><i class="fa-regular fa-image"></i></th>
                                <th>Alt Text</th>
                                <th>Acion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- TR 1 -->
                            <tr>
                                <td>
                                    <div class="data-label">S.No</div>
                                    <div>1</div>
                                </td>
                                <td>
                                    <div class="data-label"><i class="fa-regular fa-image"></i></div>
                                    <div>
                                        <img src="{{ asset('images/profile.jpg') }}" class="table-f-img">
                                    </div>
                                </td>
                                <td>
                                    <div class="data-label">Alt Text</div>
                                    <div>
                                        Product Image
                                    </div>
                                </td>
                                <td>
                                    <div class="data-label">Action</div>
                                    <div>
                                        <div class="action-row">
                                            <button class="action-btn edit" data-bs-toggle="modal"
                                                data-bs-target="#infoslid">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                            <button class="action-btn delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                            <a href="" target="_blank" rel="noopener noreferrer"
                                                class="action-btn universal">
                                                <i class="fa-solid fa-link"></i>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <ul>
                        <!--pages or li are comes from javascript -->
                    </ul>
                </div>
            </div>
        </main>

        <!-- Modal -->
        <div class="modal fade" id="infoslid" tabindex="-1" aria-labelledby="infoslidLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="hd-sm m-0">Add Sponser</h6>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <form class="needs-validation" novalidate>
                            <!-- Upload IMg -->
                            <div class="upload-box">
                                <div class="previewBox">
                                    <img src="{{ asset('images/uploadimg.svg') }}" class="preview thumb-img x2">
                                    <span><i class="fa-solid fa-rectangle-xmark"></i></span>
                                </div>
                                <div class="mt-2">
                                    <input type="file" accept="image/*" class="d-none" required>
                                    <button type="button" class="btn-xs btn-sec-outline">
                                        <i class="fa-solid fa-cloud-arrow-up i-mr"></i>
                                        Category Image*</button>
                                    <div class="invalid-feedback">
                                        Please Upload Image!!
                                    </div>
                                </div>
                            </div>

                            <!-- Alt Text -->
                            <div class="form-floating">
                                <input type="text" class="form-control" id="dg8" />
                                <label for="dg8">Alt Text*</label>
                            </div>

                            <!-- Attach Link -->
                            <div class="form-floating">
                                <input type="url" class="form-control" id="c-dfg53" required />
                                <label for="c-dfg53">Attach Link</label>
                            </div>

                            <button type="submit" class="btn-md btn-sec">Create</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
