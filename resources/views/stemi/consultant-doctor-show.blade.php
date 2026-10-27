@extends('layouts.heartscope')
@section('title', 'Doctor '.$doctor->name)
@section('content')
<div class="stemi-page-banner"><div><div class="stemi-crumb"><a href="{{ route('consultant.doctors') }}">Doctors</a> <span>›</span> {{ $doctor->name }}</div><div class="stemi-eyebrow">DOCTOR PROFILE · PREVIEW</div><h1>{{ $doctor->name }}</h1><p>{{ $doctor->specialization ?? 'Specialization not specified' }}</p></div></div>
<section class="stemi-card stemi-summary">
    <div class="stemi-card-heading"><span class="stemi-section-icon">♧</span><div><h2>Doctor Information</h2><p>Account details and examination activity.</p></div></div>
    <div class="stemi-summary-grids"><dl><div><dt>Email</dt><dd>{{ $doctor->email }}</dd></div><div><dt>Phone</dt><dd>{{ $doctor->phone ?? '—' }}</dd></div><div><dt>Status</dt><dd>{{ ucfirst($doctor->status) }}</dd></div></dl><dl><div><dt>Patients</dt><dd>{{ $patients->total() }}</dd></div><div><dt>Examinations</dt><dd>{{ $doctor->examinations_count }}</dd></div><div><dt>Active Examinations</dt><dd>{{ $doctor->active_examinations_count }}</dd></div></dl></div>
</section>
<section class="stemi-card">
    <div class="stemi-card-heading"><span class="stemi-section-icon">♙</span><div><h2>Patients Examined</h2><p>Open a patient record to preview ECG images and saved findings.</p></div></div>
    <form class="stemi-filter" method="GET"><input type="search" aria-label="Search patient ID" name="q" value="{{ $search }}" placeholder="Search Patient ID"><button class="stemi-primary-btn" type="submit">Search</button><a href="{{ route('consultant.doctors.show', $doctor) }}">Clear</a></form>
    <div class="stemi-table-scroll"><table class="stemi-table"><thead><tr><th>Patient ID</th><th>Age Category</th><th>Sex</th><th>ECG Images</th><th>Latest Examination</th><th>Action</th></tr></thead><tbody>
    @forelse($patients as $patient)<tr><td><strong>{{ $patient->subject_id }}</strong></td><td>{{ $patient->age_category ?? '—' }}</td><td>{{ $patient->sex ?? '—' }}</td><td>{{ $patient->ecg_images_count }}</td><td>{{ ucwords(str_replace('_', ' ', $patient->examinations->first()?->status ?? 'pending')) }}</td><td><a href="{{ route('consultant.doctors.patients.show', ['user' => $doctor, 'patient' => $patient]) }}">View Record @include('partials.eye-icon')</a></td></tr>@empty<tr><td colspan="6" class="stemi-empty">No patients match your search.</td></tr>@endforelse
    </tbody></table></div><div class="stemi-pagination">{{ $patients->links() }}</div>
</section>
@endsection

