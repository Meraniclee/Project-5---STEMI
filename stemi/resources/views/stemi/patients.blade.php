@extends('layouts.heartscope')
@section('title', 'Patients')
@section('content')
@php $rolePath=auth()->user()->role; @endphp
<div class="stemi-page-banner"><div><div class="stemi-crumb">{{ ucfirst($rolePath) }} Portal <span>›</span> Patients</div><div class="stemi-eyebrow">PATIENT MANAGEMENT</div><h1>{{ $scope === 'mine' ? 'My Patients' : 'Patient Records' }}</h1><p>Browse ECG examinations, image collections and patient case statuses.</p></div></div>
<section class="stemi-card">
<div class="stemi-card-heading"><span class="stemi-section-icon">♙</span><div><h2>{{ $scope === 'mine' ? 'Assigned Patient Cases' : 'All Patients' }}</h2><p>{{ $patients->total() }} records</p></div>@if(auth()->user()->isAdmin())<a class="stemi-primary-btn" href="{{ route('admin.patients.create') }}">+ Add Patient</a><a class="stemi-primary-btn" href="{{ route('admin.ecg-images.create') }}">+ Upload ECG</a>@endif</div>
<form class="stemi-filter" method="GET"><input aria-label="Search patient ID" type="search" name="q" value="{{ $search }}" placeholder="Search Patient ID"><button type="submit" class="stemi-primary-btn">Search</button><a href="{{ url()->current() }}">Clear</a></form>
<div class="stemi-table-scroll"><table class="stemi-table"><thead><tr><th>Patient ID</th><th>Age Category</th><th>Sex</th><th>ECG Images</th><th>Examinations</th><th>Examined by</th><th>Case Status</th><th>Action</th></tr></thead><tbody>
@forelse($patients as $patient)
@php $examiningDoctors = $patient->examinedExaminations->pluck('doctor')->filter()->unique('id')->values(); @endphp
<tr><td><strong>{{ $patient->subject_id }}</strong></td><td>{{ $patient->age_category ?? '—' }}</td><td>{{ $patient->sex ?? '—' }}</td><td>{{ $patient->ecg_images_count }}</td><td>{{ $patient->examinations_count }}</td>
<td>@forelse($examiningDoctors as $examiningDoctor)
    @php $doctorName = trim($examiningDoctor->name); $doctorName = preg_match('/^Dr\.?\s/i', $doctorName) ? $doctorName : 'Dr '.$doctorName; @endphp
    <div>Examined by {{ $doctorName }}</div>
@empty<span>Not yet examined</span>@endforelse</td>
<td><span class="stemi-pill {{ $patient->case_status }}">{{ ucwords(str_replace('_',' ',$patient->case_status)) }}</span></td><td><a href="{{ route($rolePath.'.patients.show', $patient) }}">View Record @include('partials.eye-icon')</a></td></tr>
@empty<tr><td colspan="8" class="stemi-empty">No matching patients. @if(auth()->user()->isAdmin())Add a patient or upload ECG images to create the first record.@endif</td></tr>@endforelse
</tbody></table></div><div class="stemi-pagination">{{ $patients->links() }}</div></section>
@endsection

