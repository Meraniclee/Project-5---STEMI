@extends('layouts.heartscope')
@section('title', "Patient's Examination Record")
@push('final-styles')<link rel="stylesheet" href="{{ asset('assets/css/stemi-reports.css') }}">@endpush
@section('content')
@php $rolePath = auth()->user()->role; @endphp
<div class="stemi-page-banner"><div><div class="stemi-crumb">Reports <span aria-hidden="true">/</span> Patient's Examination Record</div><div class="stemi-eyebrow">EXAMINATION REPORTS</div><h1>Patient's Examination Record</h1><p>{{ auth()->user()->isDoctor() ? 'Your saved examinations, including work in progress.' : 'Saved examinations by doctors, including work in progress.' }}</p></div></div>
<section class="stemi-card">
    <div class="stemi-card-heading"><span class="stemi-section-icon">▥</span><div><h2>Examined Patients</h2><p>{{ $patients->total() }} patients with saved findings</p></div></div>
    <form class="stemi-filter" method="GET"><input aria-label="Search report patient ID" type="search" name="q" value="{{ $search }}" placeholder="Search Patient ID"><button type="submit" class="stemi-primary-btn">Search</button><a href="{{ route($rolePath.'.reports') }}">Clear</a></form>
    <div class="stemi-table-scroll"><table class="stemi-table"><thead><tr><th>Patient ID</th><th>Age Category</th><th>Sex</th><th>Examined by</th><th>Saved ECG records</th><th>Last saved</th><th>Action</th></tr></thead><tbody>
    @forelse($patients as $patient)
        @php
            $findings = $patient->ecgImages->flatMap->interpretations;
            $doctors = $findings->pluck('doctor')->filter()->unique('id');
            $lastSaved = $findings->max('updated_at');
        @endphp
        <tr><td><strong>{{ $patient->subject_id }}</strong></td><td>{{ $patient->age_category ?: 'Not recorded' }}</td><td>{{ $patient->sex ?: 'Not recorded' }}</td>
            <td>@foreach($doctors as $doctor)<div>Examined by {{ \App\Support\ExaminationReport::doctorName($doctor->name) }}</div>@endforeach</td>
            <td>{{ $findings->count() }}</td><td>{{ $lastSaved?->format('d M Y H:i') }}</td>
            <td><a href="{{ route($rolePath.'.reports.show', $patient) }}">View Examination @include('partials.eye-icon')</a></td></tr>
    @empty
        <tr><td colspan="7" class="stemi-empty">No saved examination records{{ $search ? ' match this patient ID' : ' yet' }}. Records appear as soon as findings are saved.</td></tr>
    @endforelse
    </tbody></table></div>
    <div class="stemi-pagination">{{ $patients->links() }}</div>
</section>
@endsection
