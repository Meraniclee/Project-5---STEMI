<nav class="stemi-nav" aria-label="Primary navigation">
    <a class="stemi-nav-link {{ request()->routeIs($rolePath.'.dashboard') ? 'active' : '' }}" href="{{ $dashUrl }}"><span class="nav-glyph">⌂</span>Dashboard</a>
    @if($user->isAdmin())
        <a class="stemi-nav-link {{ request()->routeIs('admin.ecg-images.*') ? 'active' : '' }}" href="{{ route('admin.ecg-images.create') }}"><span class="nav-glyph">⇧</span>ECG Upload</a>
        <a class="stemi-nav-link {{ request()->routeIs('admin.patients*') ? 'active' : '' }}" href="{{ route('admin.patients') }}"><span class="nav-glyph">♙</span>Patients</a>
        <div class="stemi-nav-label">USER MANAGEMENT</div>
        <a class="stemi-nav-link {{ request()->routeIs('admin.doctors*') ? 'active' : '' }}" href="{{ route('admin.doctors') }}"><span class="nav-glyph">♧</span>Doctors</a>
        <a class="stemi-nav-link {{ request()->routeIs('admin.consultants*') ? 'active' : '' }}" href="{{ route('admin.consultants') }}"><span class="nav-glyph">♧</span>Consultants</a>
    @elseif($user->isDoctor())
        <div class="stemi-nav-group {{ request()->routeIs('doctor.patients*','doctor.my-patients') ? 'open' : '' }}">
            <a class="stemi-nav-link {{ request()->routeIs('doctor.patients*','doctor.my-patients') ? 'active' : '' }}" href="{{ route('doctor.patients') }}"><span class="nav-glyph">♙</span>Patients <span style="margin-left:auto">@include('partials.eye-icon')</span></a>
            <div class="stemi-nav-sub">
                <a class="{{ request()->routeIs('doctor.patients') ? 'selected' : '' }}" href="{{ route('doctor.patients') }}">Available Patients</a>
                <a class="{{ request()->routeIs('doctor.my-patients','doctor.patients.show') ? 'selected' : '' }}" href="{{ route('doctor.my-patients') }}">My Patients</a>
                @if(request()->routeIs('doctor.patients.show')) <span class="selected">Patient Record</span> @endif
            </div>
        </div>
        <a class="stemi-nav-link {{ request()->routeIs('doctor.my-cases') ? 'active' : '' }}" href="{{ route('doctor.my-cases') }}"><span class="nav-glyph">▤</span>My Cases</a>
    @else
        <a class="stemi-nav-link {{ request()->routeIs('consultant.doctors*') ? 'active' : '' }}" href="{{ route('consultant.doctors') }}"><span class="nav-glyph">♧</span>Doctors</a>
        <a class="stemi-nav-link {{ request()->routeIs('consultant.patients*') ? 'active' : '' }}" href="{{ route('consultant.patients') }}"><span class="nav-glyph">♙</span>Patients</a>
        <a class="stemi-nav-link {{ request()->routeIs('consultant.case-status') ? 'active' : '' }}" href="{{ route('consultant.case-status') }}"><span class="nav-glyph">▤</span>Case Status</a>
    @endif
    <a class="stemi-nav-link {{ request()->routeIs($rolePath.'.reports') ? 'active' : '' }}" href="{{ route($rolePath.'.reports') }}"><span class="nav-glyph">▥</span>Reports</a>
    @unless($user->isAdmin()) <a class="stemi-nav-link {{ request()->routeIs($rolePath.'.profile') ? 'active' : '' }}" href="{{ route($rolePath.'.profile') }}"><span class="nav-glyph">♙</span>Profile</a> @endunless
</nav>
