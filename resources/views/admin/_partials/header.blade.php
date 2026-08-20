<header class="header">
    <div class="leftSid">
        <!-- toggle menu btn  -->
        <div class="sidebar-toggle">
            <i class="fa-solid fa-bars-staggered"></i>
        </div>
        <img class="logo" src="{{ asset('images/logo.svg') }}" alt="Dolphinevent logo">
    </div>

    <div class="rightSid">
        <!-- Dark / Light mode switch -->
        <div class="mode">
            <button type="button" class="mode-toggle top-btn">
                <i class="fa-solid fa-sun"></i>
                <i class="fa-regular fa-moon"></i>
            </button>
        </div>

        <!-- Profile dropdown  -->
        @php
            $authUser = auth()->user();
            $roleName = \App\Models\Role::whereKey($authUser->role)->value('name')
                ?? data_get(config('entities.user_types', []), $authUser->role, 'unknown');
        @endphp
        <div class="dropdown profile">
            <button class="dropdown-toggle top-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ $authUser->profile_picture && file_exists(storage_path('app/public/' . $authUser->profile_picture)) ? asset('storage/' . $authUser->profile_picture) : asset('images/defult_user.png') }}" class="profile-img" />
                <span>{{ $authUser->name }}</span>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <h6 class="Uname">{{ '@' . $authUser->username }}</h6>
                    <h6 class="Upost text-capitalize">Role: {{ $roleName }}</h6>
                </li>
                <li>
                    <a class="dropdown-item navJS" href="{{ route('profile') }}">
                        <i class="fa-regular fa-circle-user"></i>
                        <span class="likName">Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item navJS" href="{{ route('profile.edit') }}">
                        <i class="fa-solid fa-user-pen"></i>
                        <span class="likName">Edit Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('website.home.index') }}" target="_blank">
                        <i class="fa-solid fa-earth-europe"></i>
                        <span class="likName">Back to Site</span>
                    </a>
                </li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item logOut">
                            <i class="fa-solid fa-right-from-bracket pe-none"></i>
                            <span class="likName pe-none">Logout</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<div class="top-space"></div>
