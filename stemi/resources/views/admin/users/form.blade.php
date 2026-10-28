@extends('layouts.heartscope')

@php

    $isDoctor =
        $role === 'doctor';

    $isEdit =
        $mode === 'edit';

    $singularLabel =
        $isDoctor
            ? 'Doctor'
            : 'Consultant';

    $pluralRoute =
        $isDoctor
            ? route('admin.doctors')
            : route('admin.consultants');

    $submitRoute =
        $isEdit
            ? (
                $isDoctor
                    ? route(
                        'admin.doctors.update',
                        $user
                    )
                    : route(
                        'admin.consultants.update',
                        $user
                    )
            )
            : (
                $isDoctor
                    ? route(
                        'admin.doctors.store'
                    )
                    : route(
                        'admin.consultants.store'
                    )
            );

@endphp


@section(
    'title',
    ($isEdit ? 'Edit ' : 'Add ') .
    $singularLabel .
    ' | Stemi'
)


@push('styles')

<link
    rel="stylesheet"
    href="{{ asset('assets/css/admin-users.css') }}"
>

@endpush


@section('content')

<section class="management-header">

    <div>

        <a
            href="{{ $pluralRoute }}"
            class="management-back-link"
        >
            @include('partials.eye-icon') Back to {{ $singularLabel }} Management
        </a>

        <h1>
            {{ $isEdit ? 'Edit' : 'Add' }}
            {{ $singularLabel }}
        </h1>

        <p>
            @if($isEdit)

                Update account information,
                access status and credentials.

            @else

                Create a new
                {{ strtolower($singularLabel) }}
                account for Stemi.

            @endif
        </p>

    </div>

</section>


<section class="user-form-layout">

    <form
        method="POST"
        action="{{ $submitRoute }}"
        class="user-form-card"
    >

        @csrf

        @if($isEdit)
            @method('PUT')
        @endif


        <div class="form-section-heading">

            <span class="form-section-icon">

                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="7" r="4"/>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
                </svg>

            </span>

            <div>

                <h2>
                    Account Information
                </h2>

                <p>
                    Basic identity and account details.
                </p>

            </div>

        </div>


        @if($errors->any())

            <div class="form-error-summary">

                <strong>
                    Please correct the following:
                </strong>

                <ul>

                    @foreach($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        <div class="user-form-grid">

            <div class="user-field full-width">

                <label for="name">
                    Full Name
                    <span>*</span>
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old(
                        'name',
                        $user?->name
                    ) }}"
                    placeholder="{{ $isDoctor
                        ? 'e.g. Dr. Sarah Lim'
                        : 'e.g. Sarah Lim' }}"
                    required
                >

            </div>


            <div class="user-field">

                <label for="email">
                    Email Address
                    <span>*</span>
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old(
                        'email',
                        $user?->email
                    ) }}"
                    placeholder="name@hospital.edu"
                    required
                >

            </div>


            <div class="user-field">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    id="phone"
                    type="text"
                    name="phone"
                    value="{{ old(
                        'phone',
                        $user?->phone
                    ) }}"
                    placeholder="+60 12-345 6789"
                >

            </div>


            @if($isDoctor)

                <div class="user-field">

                    <label for="specialization">
                        Specialization
                        <span>*</span>
                    </label>

                    <input
                        id="specialization"
                        type="text"
                        name="specialization"
                        value="{{ old(
                            'specialization',
                            $user?->specialization
                        ) }}"
                        placeholder="e.g. Cardiology"
                        required
                    >

                </div>

            @else

                <input
                    type="hidden"
                    name="specialization"
                    value=""
                >

            @endif


            <div class="user-field">

                <label for="status">
                    Account Status
                    <span>*</span>
                </label>

                <select
                    id="status"
                    name="status"
                    required
                >

                    <option
                        value="active"
                        @selected(
                            old(
                                'status',
                                $user?->status ?? 'active'
                            ) === 'active'
                        )
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        @selected(
                            old(
                                'status',
                                $user?->status ?? 'active'
                            ) === 'inactive'
                        )
                    >
                        Inactive
                    </option>

                </select>

            </div>

        </div>


        <div class="form-divider"></div>


        <div class="form-section-heading">

            <span class="form-section-icon password">

                <svg viewBox="0 0 24 24">
                    <rect
                        x="5"
                        y="10"
                        width="14"
                        height="11"
                        rx="2"
                    />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>

            </span>

            <div>

                <h2>
                    Password
                </h2>

                <p>
                    @if($isEdit)

                        Leave both password fields empty
                        to keep the existing password.

                    @else

                        Create a password with at least
                        8 characters.

                    @endif
                </p>

            </div>

        </div>


        <div class="user-form-grid">

            <div class="user-field">

                <label for="password">
                    Password

                    @unless($isEdit)
                        <span>*</span>
                    @endunless
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    placeholder="{{ $isEdit
                        ? 'Leave blank to keep current password'
                        : 'Minimum 8 characters' }}"
                    {{ $isEdit ? '' : 'required' }}
                >

            </div>


            <div class="user-field">

                <label for="password_confirmation">
                    Confirm Password

                    @unless($isEdit)
                        <span>*</span>
                    @endunless
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Repeat password"
                    {{ $isEdit ? '' : 'required' }}
                >

            </div>

        </div>


        <div class="user-form-actions">

            <a
                href="{{ $pluralRoute }}"
                class="form-cancel-button"
            >
                Cancel
            </a>


            <button
                type="submit"
                class="form-submit-button"
            >

                {{ $isEdit
                    ? 'Save Changes'
                    : 'Create ' . $singularLabel }}

            </button>

        </div>

    </form>


    <aside class="user-form-side-card">

        <div class="side-card-icon">

            @if($isDoctor)

                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="6" r="4"/>
                    <path d="M5 21v-3a7 7 0 0 1 14 0v3"/>
                    <path d="M9 14v3"/>
                    <path d="M15 14v3"/>
                    <path d="M9 17h6"/>
                </svg>

            @else

                <svg viewBox="0 0 24 24">
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                    <circle cx="18" cy="9" r="3"/>
                </svg>

            @endif

        </div>


        <h2>
            {{ $singularLabel }} Access
        </h2>


        @if($isDoctor)

            <p>
                Doctors will be able to access
                patient examination workflows and
                inspect AV, PV, TV and MV heart
                ECG images.
            </p>

            <ul>
                <li>
                    Patient examination
                </li>

                <li>
                    ECG image playback
                </li>

                <li>
                    Clinical findings
                </li>

                <li>
                    Save draft / complete examination
                </li>
            </ul>

        @else

            <p>
                Consultants receive read-only access
                for monitoring patient and
                examination status.
            </p>

            <ul>
                <li>
                    Case status
                </li>

                <li>
                    Patient records
                </li>

                <li>
                    Reports
                </li>

                <li>
                    Read-only monitoring
                </li>
            </ul>

        @endif


        <div class="side-security-note">

            <strong>
                Role protection
            </strong>

            <span>
                Stemi automatically limits
                access according to the assigned
                user role.
            </span>

        </div>

    </aside>

</section>

@endsection
