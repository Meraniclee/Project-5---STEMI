@extends('layouts.heartscope')



@php

    $isDoctor = $role === 'doctor';



    $pluralLabel = $isDoctor

        ? 'Doctors'

        : 'Consultants';



    $singularLabel = $isDoctor

        ? 'Doctor'

        : 'Consultant';



    $createRoute = $isDoctor

        ? route('admin.doctors.create')

        : route('admin.consultants.create');



    $indexRoute = $isDoctor

        ? route('admin.doctors')

        : route('admin.consultants');

@endphp



@section(

    'title',

    $pluralLabel . ' | Stemi'

)



@push('styles')



<link

    rel="stylesheet"

    href="{{ asset('assets/css/admin-users.css') }}"

\>



<style>



    /\*

    |--------------------------------------------------------------------------

    | Patients Managed Indicator

    |--------------------------------------------------------------------------

    \*/



    .management-patients-managed {

        display: inline-flex;

        align-items: center;

        justify-content: center;



        gap: 6px;



        min-width: 82px;

        min-height: 28px;



        padding:

            0

            9px;



        border:

            1px solid

            #cce7f5;



        border-radius: 8px;



        color:

            #0879b9;



        background:

            #eef9fe;



        font-size: 9px;

        font-weight: 800;



        white-space: nowrap;

    }



    .management-patients-managed svg {

        width: 14px;

        height: 14px;



        fill: none;



        stroke:

            currentColor;



        stroke-width: 1.8;



        stroke-linecap:

            round;



        stroke-linejoin:

            round;

    }



    .management-patients-managed.zero {

        color:

            #718697;



        border-color:

            #dce7ed;



        background:

            #f4f7f9;

    }



</style>



@endpush





@section('content')



<section class="management-header">



    <div>



        <span class="management-eyebrow">

            USER MANAGEMENT

        </span>



        <h1>

            {{ $pluralLabel }}

        </h1>



        <p>

            @if($isDoctor)



                Add, edit, activate, deactivate and

                manage Doctor accounts responsible for

                patient ECG image examinations.



            @else



                Add, edit, activate, deactivate and

                manage Consultant accounts with

                read-only monitoring access.



            @endif

        </p>



    </div>





    <a

        href="{{ $createRoute }}"

        class="management-primary-button"

    >

        <span class="button-plus">

            +

        </span>



        Add {{ $singularLabel }}

    </a>



</section>





{{-- =========================================================

     SUMMARY

\========================================================= --}}



<section class="management-stat-grid">



    <article class="management-stat-card">



        <span class="management-stat-icon total">



            <svg viewBox="0 0 24 24">

                <circle cx="9" cy="7" r="4"/>

                <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>

                <circle cx="18" cy="9" r="3"/>

            </svg>



        </span>



        <div>



            <span>

                Total {{ $pluralLabel }}

            </span>



            <strong>

                {{ number_format($totalUsers) }}

            </strong>



        </div>



    </article>





    <article class="management-stat-card">



        <span class="management-stat-icon active">



            <svg viewBox="0 0 24 24">

                <circle cx="12" cy="12" r="9"/>

                <path d="m8 12 3 3 5-6"/>

            </svg>



        </span>



        <div>



            <span>

                Active

            </span>



            <strong>

                {{ number_format($activeUsers) }}

            </strong>



        </div>



    </article>





    <article class="management-stat-card">



        <span class="management-stat-icon inactive">



            <svg viewBox="0 0 24 24">

                <circle cx="12" cy="12" r="9"/>

                <path d="M8 12h8"/>

            </svg>



        </span>



        <div>



            <span>

                Inactive

            </span>



            <strong>

                {{ number_format($inactiveUsers) }}

            </strong>



        </div>



    </article>



</section>





{{-- =========================================================

     SEARCH / FILTER

\========================================================= --}}



<section class="management-panel">



    <div class="management-panel-heading">



        <div>



            <h2>

                {{ $singularLabel }} Accounts

            </h2>



            <p>

                Search and manage registered

                {{ strtolower($pluralLabel) }}.

            </p>



        </div>



    </div>





    <form

        method="GET"

        action="{{ $indexRoute }}"

        class="management-filter-form"

    >



        <div class="management-search-field">



            <svg viewBox="0 0 24 24">

                <circle cx="11" cy="11" r="7"/>

                <path d="m20 20-4-4"/>

            </svg>



            <input

                type="search"

                name="q"

                value="{{ $search }}"

                placeholder="Search by name, email, phone or specialization..."

            >



        </div>





        <select

            name="status"

            aria-label="Filter by status"

        >



            <option

                value="all"

                @selected($status === 'all')

            >

                All Status

            </option>



            <option

                value="active"

                @selected($status === 'active')

            >

                Active

            </option>



            <option

                value="inactive"

                @selected($status === 'inactive')

            >

                Inactive

            </option>



        </select>





        <button

            type="submit"

            class="management-filter-button"

        >

            Apply

        </button>





        <a

            href="{{ $indexRoute }}"

            class="management-reset-button"

        >

            Reset

        </a>



    </form>





    {{-- =====================================================

         DESKTOP TABLE

    ====================================================== --}}



    <div class="management-table-wrapper">



        <table class="management-table">



            <thead>



                <tr>



                    <th>

                        User

                    </th>



                    <th>

                        Contact

                    </th>





                    @if($isDoctor)



                        <th>

                            Specialization

                        </th>



                    @endif





                    <th>

                        Status

                    </th>



                    <th>

                        Last Login

                    </th>





                    @if($isDoctor)



                        <th>

                            Patients Managed

                        </th>



                    @endif





                    <th>

                        Actions

                    </th>



                </tr>



            </thead>





            <tbody>



            @forelse($users as $managedUser)



                <tr>



                    {{-- =================================================

                         USER

                    ================================================== --}}



                    <td>



                        <div class="management-user-cell">



                            <span class="management-avatar">



                                {{ strtoupper(

                                    substr(

                                        $managedUser->name,

                                        0,

                                        1

                                    )

                                ) }}



                            </span>





                            <div>



                                <strong>

                                    {{ $managedUser->name }}

                                </strong>



                                <span>



                                    ID:



                                    {{ str_pad(

                                        $managedUser->id,

                                        4,

                                        '0',

                                        STR_PAD_LEFT

                                    ) }}



                                </span>



                            </div>



                        </div>



                    </td>





                    {{-- =================================================

                         CONTACT

                    ================================================== --}}



                    <td>



                        <div class="management-contact">



                            <strong>

                                {{ $managedUser->email }}

                            </strong>



                            <span>



                                {{ $managedUser->phone

                                    ?? 'No phone number' }}



                            </span>



                        </div>



                    </td>





                    {{-- =================================================

                         SPECIALIZATION

                    ================================================== --}}



                    @if($isDoctor)



                        <td>



                            {{ $managedUser->specialization

                                ?? '—' }}



                        </td>



                    @endif





                    {{-- =================================================

                         STATUS

                    ================================================== --}}



                    <td>



                        <span

                            class="management-status

                            {{ $managedUser->status === 'active'

                                ? 'active'

                                : 'inactive' }}"

                        >



                            {{ ucfirst(

                                $managedUser->status

                            ) }}



                        </span>



                    </td>





                    {{-- =================================================

                         LAST LOGIN

                    ================================================== --}}



                    <td>



                        @if($managedUser->last_login_at)



                            {{ $managedUser

                                ->last_login_at

                                ->format(

                                    'd M Y, h:i A'

                                ) }}



                        @else



                            <span class="management-muted">

                                Never

                            </span>



                        @endif



                    </td>





                    {{-- =================================================

                         PATIENTS MANAGED

                    ================================================== --}}



                    @if($isDoctor)



                        <td>



                            @php



                                $patientsManagedCount =

                                    (int) (

                                        $managedUser

                                            ->patients_managed_count

                                        ?? 0

                                    );



                            @endphp
                            <a
                                href="{{ route(
                                    'admin.doctors.show',
                                    $managedUser
                                ) }}"
                                class="management-patients-managed
                                {{ $patientsManagedCount === 0
                                    ? 'zero'
                                    : '' }}"
                                title="View patients managed by {{ $managedUser->name }}"
                            >



                                <svg

                                    viewBox="0 0 24 24"

                                    aria-hidden="true"

                                >



                                    <circle

                                        cx="9"

                                        cy="8"

                                        r="3"

                                    />



                                    <path

                                        d="M3 20v-2a6 6 0 0 1 12 0v2"

                                    />



                                    <path

                                        d="M17 11v6"

                                    />



                                    <path

                                        d="M14 14h6"

                                    />



                                </svg>





                                {{ number_format(

                                    $patientsManagedCount

                                ) }}



                                {{ $patientsManagedCount === 1

                                    ? 'patient'

                                    : 'patients' }}



                            </a>



                        </td>



                    @endif





                    {{-- =================================================

                         ACTIONS

                    ================================================== --}}



                    <td>



                        <div class="management-actions">

                            @if($isDoctor)
                                <a
                                    href="{{ route(
                                        'admin.doctors.show',
                                        $managedUser
                                    ) }}"
                                    class="action-button view"
                                    title="View Doctor"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                                        <circle cx="12" cy="12" r="2.5"/>
                                    </svg>
                                    <span>View</span>
                                </a>
                            @endif




                            <a

                                href="{{ $isDoctor

                                    ? route(

                                        'admin.doctors.edit',

                                        $managedUser

                                    )

                                    : route(

                                        'admin.consultants.edit',

                                        $managedUser

                                    ) }}"

                                class="action-button edit"

                                title="Edit"

                            >



                                <svg viewBox="0 0 24 24">



                                    <path

                                        d="M12 20h9"

                                    />



                                    <path

                                        d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"

                                    />



                                </svg>



                                <span>

                                    Edit

                                </span>



                            </a>





                            <form

                                method="POST"

                                action="{{ $isDoctor

                                    ? route(

                                        'admin.doctors.status',

                                        $managedUser

                                    )

                                    : route(

                                        'admin.consultants.status',

                                        $managedUser

                                    ) }}"

                            >



                                @csrf

                                @method('PATCH')



                                <button

                                    type="submit"

                                    class="action-button

                                    {{ $managedUser->status === 'active'

                                        ? 'deactivate'

                                        : 'activate' }}"

                                >



                                    @if($managedUser->status === 'active')



                                        Deactivate



                                    @else



                                        Activate



                                    @endif



                                </button>



                            </form>





                            <form

                                method="POST"

                                action="{{ $isDoctor

                                    ? route(

                                        'admin.doctors.destroy',

                                        $managedUser

                                    )

                                    : route(

                                        'admin.consultants.destroy',

                                        $managedUser

                                    ) }}"

                                class="delete-user-form"

                                data-user-name="{{ $managedUser->name }}"

                            >



                                @csrf

                                @method('DELETE')



                                <button

                                    type="submit"

                                    class="action-button delete"

                                >

                                    Delete

                                </button>



                            </form>



                        </div>



                    </td>



                </tr>





            @empty



                <tr>



                    <td

                        colspan="{{ $isDoctor ? 7 : 5 }}"

                        class="management-empty"

                    >



                        <strong>

                            No {{ strtolower($pluralLabel) }} found.

                        </strong>



                        <span>



                            Change the search/filter or

                            add a new

                            {{ strtolower($singularLabel) }}.



                        </span>



                    </td>



                </tr>



            @endforelse



            </tbody>



        </table>



    </div>





    {{-- =====================================================

         MOBILE CARDS

    ====================================================== --}}



    <div class="management-mobile-list">



        @forelse($users as $managedUser)



            <article
                class="management-mobile-card
                {{ $isDoctor ? 'is-doctor-card' : 'is-consultant-card' }}"
            >



                <div class="mobile-user-heading">



                    <span class="management-avatar large">



                        {{ strtoupper(

                            substr(

                                $managedUser->name,

                                0,

                                1

                            )

                        ) }}



                    </span>





                    <div>



                        <strong>

                            {{ $managedUser->name }}

                        </strong>



                        <span>

                            {{ $managedUser->email }}

                        </span>



                    </div>





                    <span

                        class="management-status

                        {{ $managedUser->status === 'active'

                            ? 'active'

                            : 'inactive' }}"

                    >



                        {{ ucfirst(

                            $managedUser->status

                        ) }}



                    </span>



                </div>





                <dl class="mobile-user-details">





                    @if($isDoctor)



                        <div>



                            <dt>

                                Specialization

                            </dt>



                            <dd>



                                {{ $managedUser->specialization

                                    ?? '—' }}



                            </dd>



                        </div>



                    @endif





                    <div>



                        <dt>

                            Phone

                        </dt>



                        <dd>



                            {{ $managedUser->phone

                                ?? '—' }}



                        </dd>



                    </div>





                    <div>



                        <dt>

                            Last Login

                        </dt>



                        <dd>



                            {{ $managedUser->last_login_at

                                ? $managedUser

                                    ->last_login_at

                                    ->format(

                                        'd M Y, h:i A'

                                    )

                                : 'Never' }}



                        </dd>



                    </div>





                    @if($isDoctor)



                        <div>



                            <dt>

                                Patients Managed

                            </dt>



                            <dd>



                                {{ number_format(

                                    (int) (

                                        $managedUser

                                            ->patients_managed_count

                                        ?? 0

                                    )

                                ) }}



                                unique patient(s)



                            </dd>



                        </div>



                    @endif



                </dl>





                <div class="mobile-user-actions">

                    @if($isDoctor)
                        <a
                            href="{{ route(
                                'admin.doctors.show',
                                $managedUser
                            ) }}"
                            class="action-button view"
                        >
                            View
                        </a>
                    @endif




                    <a

                        href="{{ $isDoctor

                            ? route(

                                'admin.doctors.edit',

                                $managedUser

                            )

                            : route(

                                'admin.consultants.edit',

                                $managedUser

                            ) }}"

                        class="action-button edit"

                    >

                        Edit

                    </a>





                    <form

                        method="POST"

                        action="{{ $isDoctor

                            ? route(

                                'admin.doctors.status',

                                $managedUser

                            )

                            : route(

                                'admin.consultants.status',

                                $managedUser

                            ) }}"

                    >



                        @csrf

                        @method('PATCH')



                        <button

                            type="submit"

                            class="action-button

                            {{ $managedUser->status === 'active'

                                ? 'deactivate'

                                : 'activate' }}"

                        >



                            {{ $managedUser->status === 'active'

                                ? 'Deactivate'

                                : 'Activate' }}



                        </button>



                    </form>





                    <form

                        method="POST"

                        action="{{ $isDoctor

                            ? route(

                                'admin.doctors.destroy',

                                $managedUser

                            )

                            : route(

                                'admin.consultants.destroy',

                                $managedUser

                            ) }}"

                        class="delete-user-form"

                        data-user-name="{{ $managedUser->name }}"

                    >



                        @csrf

                        @method('DELETE')



                        <button

                            type="submit"

                            class="action-button delete"

                        >

                            Delete

                        </button>



                    </form>



                </div>



            </article>





        @empty



            <div class="management-mobile-empty">



                No {{ strtolower($pluralLabel) }} found.



            </div>



        @endforelse



    </div>





    {{-- =====================================================

         PAGINATION

    ====================================================== --}}



    @if($users->hasPages())



        <div class="management-pagination">



            @if($users->onFirstPage())



                <span class="pagination-disabled">

                    @include('partials.eye-icon') Previous

                </span>



            @else



                <a href="{{ $users->previousPageUrl() }}">

                    @include('partials.eye-icon') Previous

                </a>



            @endif





            <span>



                Page

                {{ $users->currentPage() }}

                of

                {{ $users->lastPage() }}



            </span>





            @if($users->hasMorePages())



                <a href="{{ $users->nextPageUrl() }}">

                    Next @include('partials.eye-icon')

                </a>



            @else



                <span class="pagination-disabled">

                    Next @include('partials.eye-icon')

                </span>



            @endif



        </div>



    @endif



</section>



@endsection





@push('scripts')



<script>



    document

        .querySelectorAll('.delete-user-form')

        .forEach((form) => {



            form.addEventListener(

                'submit',

                (event) => {



                    const userName =

                        form.dataset.userName;



                    const confirmed =

                        window\.confirm(

                            \`Remove ${userName}? This action cannot be undone.\`

                        );



                    if (!confirmed) {

                        event.preventDefault();

                    }



                }

            );



        });



</script>



@endpush
