@extends('layouts.heartscope')

@section('title', 'Doctor Profile | STEMI')

@push('styles')
<link
    rel="stylesheet"
    href="{{ asset('assets/css/admin-users.css') }}"
>
<link
    rel="stylesheet"
    href="{{ asset('assets/css/admin-doctor-show.css') }}"
>
@endpush

@section('content')

<section class="doctor-review-header">
    <div>
        <nav class="doctor-review-breadcrumb" aria-label="Doctor breadcrumb">
            <a href="{{ route('admin.doctors') }}">Doctors</a>
            <span aria-hidden="true">›</span>
            <strong>{{ $doctor->name }}</strong>
        </nav>

        <span class="management-eyebrow">USER MANAGEMENT</span>
        <h1>{{ $doctor->name }}</h1>
        <p>
            Review this Doctor's account, managed patients and examination activity.
        </p>
    </div>

    <a
        href="{{ route('admin.doctors.edit', $doctor) }}"
        class="management-primary-button doctor-review-edit"
    >
        Edit Doctor
    </a>
</section>

<section class="doctor-profile-card">
    <div class="doctor-profile-identity">
        <span class="doctor-profile-avatar">
            {{ strtoupper(substr($doctor->name, 0, 1)) }}
        </span>
        <div>
            <span class="doctor-profile-kicker">DOCTOR PROFILE</span>
            <h2>{{ $doctor->name }}</h2>
            <p>{{ $doctor->specialization ?? 'Specialization not specified' }}</p>
        </div>
    </div>

    <dl class="doctor-profile-meta">
        <div>
            <dt>Email</dt>
            <dd>{{ $doctor->email }}</dd>
        </div>
        <div>
            <dt>Phone</dt>
            <dd>{{ $doctor->phone ?? '—' }}</dd>
        </div>
        <div>
            <dt>Status</dt>
            <dd>
                <span class="management-status {{ $doctor->status === 'active' ? 'active' : 'inactive' }}">
                    {{ ucfirst($doctor->status) }}
                </span>
            </dd>
        </div>
        <div>
            <dt>Last Login</dt>
            <dd>
                {{ $doctor->last_login_at
                    ?->format('d M Y, h:i A')
                    ?? 'Never' }}
            </dd>
        </div>
    </dl>
</section>

<section class="doctor-review-stat-grid">
    <article class="doctor-review-stat-card">
        <span>Patients Managed</span>
        <strong>{{ number_format($totalPatients) }}</strong>
        <small>Unique patients examined</small>
    </article>

    <article class="doctor-review-stat-card">
        <span>Total Examinations</span>
        <strong>{{ number_format($totalExaminations) }}</strong>
        <small>All examinations by this Doctor</small>
    </article>

    <article class="doctor-review-stat-card">
        <span>Active Examinations</span>
        <strong>{{ number_format($activeExaminations) }}</strong>
        <small>Draft or in progress</small>
    </article>

    <article class="doctor-review-stat-card">
        <span>Completed</span>
        <strong>{{ number_format($completedExaminations) }}</strong>
        <small>Completed examinations</small>
    </article>
</section>

<section class="management-panel doctor-patients-panel">
    <div class="management-panel-heading">
        <div>
            <h2>Patients Managed</h2>
            <p>
                Patients examined by {{ $doctor->name }}. Open a patient to review
                ECG images and this Doctor's recorded examination findings.
            </p>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('admin.doctors.show', $doctor) }}"
        class="doctor-patient-filter-form"
    >
        <div class="management-search-field">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-4-4"/>
            </svg>
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Search Patient ID, age group or sex..."
            >
        </div>

        <select name="status" aria-label="Filter patients by status">
            <option value="all" @selected($status === 'all')>All Status</option>
            <option value="pending" @selected($status === 'pending')>Pending</option>
            <option value="in_progress" @selected($status === 'in_progress')>In Progress</option>
            <option value="completed" @selected($status === 'completed')>Completed</option>
            <option value="needs_review" @selected($status === 'needs_review')>Needs Review</option>
        </select>

        <button type="submit" class="management-filter-button">Apply</button>
        <a href="{{ route('admin.doctors.show', $doctor) }}" class="management-reset-button">Reset</a>
    </form>

    <div class="management-table-wrapper doctor-patient-table-wrapper">
        <table class="management-table doctor-patient-table">
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Age Group</th>
                    <th>Sex</th>
                    <th>ECG Images</th>
                    <th>Doctor Exams</th>
                    <th>Status</th>
                    <th>Last Examined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($patients as $patient)
                    @php
                        $latestDoctorExamination = $patient->examinations->first();
                        $lastExaminedAt =
                            $latestDoctorExamination?->completed_at
                            ?? $latestDoctorExamination?->started_at
                            ?? $latestDoctorExamination?->created_at;

                        $statusLabel = ucwords(
                            str_replace('_', ' ', $patient->case_status)
                        );
                    @endphp

                    <tr>
                        <td>
                            <strong class="doctor-patient-id">
                                {{ $patient->subject_id }}
                            </strong>
                        </td>
                        <td>{{ $patient->age_category ?? '—' }}</td>
                        <td>{{ $patient->sex ?? '—' }}</td>
                        <td>
                            <span class="doctor-patient-count-badge">
                                {{ number_format($patient->ecg_images_count) }}
                            </span>
                        </td>
                        <td>{{ number_format($patient->doctor_examinations_count) }}</td>
                        <td>
                            <span class="doctor-patient-status status-{{ str_replace('_', '-', $patient->case_status) }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>
                            {{ $lastExaminedAt?->format('d M Y, h:i A') ?? '—' }}
                        </td>
                        <td>
                            <a
                                href="{{ route(
                                    'admin.doctors.patients.show',
                                    [
                                        'user' => $doctor,
                                        'patient' => $patient,
                                    ]
                                ) }}"
                                class="action-button view"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="doctor-patient-empty">
                            No managed patients match the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- =====================================================
         MOBILE PATIENT CARDS
    ====================================================== --}}

    <div class="management-mobile-list doctor-patient-mobile-list">
        @forelse ($patients as $patient)
            @php
                $latestDoctorExamination = $patient->examinations->first();
                $lastExaminedAt =
                    $latestDoctorExamination?->completed_at
                    ?? $latestDoctorExamination?->started_at
                    ?? $latestDoctorExamination?->created_at;

                $statusLabel = ucwords(
                    str_replace('_', ' ', $patient->case_status)
                );
            @endphp

            <article class="management-mobile-card doctor-patient-mobile-card">
                <div class="doctor-patient-mobile-heading">
                    <div>
                        <span class="doctor-patient-mobile-kicker">PATIENT</span>
                        <strong>
                            {{ $patient->subject_id }}
                        </strong>
                    </div>

                    <span class="doctor-patient-status status-{{ str_replace('_', '-', $patient->case_status) }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <dl class="doctor-patient-mobile-details">
                    <div>
                        <dt>Age Group</dt>
                        <dd>{{ $patient->age_category ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt>Sex</dt>
                        <dd>{{ $patient->sex ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt>ECG Images</dt>
                        <dd>
                            <span class="doctor-patient-count-badge">
                                {{ number_format($patient->ecg_images_count) }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt>Doctor Exams</dt>
                        <dd>{{ number_format($patient->doctor_examinations_count) }}</dd>
                    </div>

                    <div class="doctor-patient-mobile-detail-wide">
                        <dt>Last Examined</dt>
                        <dd>{{ $lastExaminedAt?->format('d M Y, h:i A') ?? '—' }}</dd>
                    </div>
                </dl>

                <a
                    href="{{ route(
                        'admin.doctors.patients.show',
                        [
                            'user' => $doctor,
                            'patient' => $patient,
                        ]
                    ) }}"
                    class="action-button view doctor-patient-mobile-view"
                >
                    View Patient Record
                </a>
            </article>
        @empty
            <div class="management-mobile-empty">
                No managed patients match the selected filters.
            </div>
        @endforelse
    </div>

    @if ($patients->hasPages())
        <div class="doctor-patient-pagination">
            {{ $patients->links() }}
        </div>
    @endif
</section>

@endsection
