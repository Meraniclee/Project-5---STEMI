@extends('layouts.heartscope')
@section('title', 'Dashboard')
@section('content')
@php
    $user = auth()->user();
    $role = $user->isAdmin() ? 'Administrator' : ($user->isDoctor() ? 'Doctor' : 'Consultant');
    $path = $user->role;
@endphp
<div class="stemi-page-banner">
    <div><div class="stemi-crumb">{{ $role }} Portal <span>›</span> Dashboard</div><div class="stemi-eyebrow">{{ strtoupper($role) }} OVERVIEW</div>
    <h1>Welcome back, {{ $user->name }}</h1>
    <p>{{ $user->isAdmin() ? 'Manage ECG images, patient records and clinical team activity.' : ($user->isDoctor() ? 'Review patient ECG images and document your clinical interpretations.' : 'Monitor patient ECG examinations and completed findings.') }}</p></div>
</div>
<div class="stemi-kpis">
    @foreach($stats as $stat)
        <div class="stemi-kpi"><div class="stemi-kpi-top"><span>{{ $stat['label'] }}</span><span class="stemi-kpi-symbol">{{ ['patients'=>'♙','images'=>'▧','complete'=>'✓','review'=>'◉'][$stat['icon']] }}</span></div><strong>{{ number_format($stat['value']) }}</strong><small>Current system records</small></div>
    @endforeach
</div>
<div class="stemi-two-panels">
    <section class="stemi-card"><div class="stemi-card-heading"><span class="stemi-section-icon">♙</span><div><h2>Recent Patients</h2><p>ECG image records requiring review or follow-up</p></div><a class="stemi-text-link" href="{{ route($path.'.patients') }}">View all @include('partials.eye-icon')</a></div>
        <div class="stemi-table-scroll"><table class="stemi-table"><thead><tr><th>Patient ID</th><th>Age</th><th>ECG Images</th><th>Case Status</th><th></th></tr></thead><tbody>
        @forelse($patients as $patient)
        <tr><td><strong>{{ $patient->subject_id }}</strong></td><td>{{ $patient->age_category ?? 'Not provided' }}</td><td>{{ $patient->ecg_images_count }}</td><td><span class="stemi-pill {{ $patient->case_status }}">{{ ucwords(str_replace('_',' ',$patient->case_status)) }}</span></td><td><a href="{{ route($path.'.patients.show', $patient) }}">View @include('partials.eye-icon')</a></td></tr>
        @empty <tr><td colspan="5" class="stemi-empty">No ECG patient records yet.</td></tr> @endforelse
        </tbody></table></div>
    </section>
    <section class="stemi-card"><div class="stemi-card-heading"><span class="stemi-section-icon">▤</span><div><h2>Quick Actions</h2><p>Frequently used tools for your role</p></div></div>
        <div class="stemi-actions-list">
            @if($user->isAdmin())
                <a href="{{ route('admin.ecg-images.create') }}"><span class="stemi-action-circle">⇧</span><span><strong>Upload ECG Images</strong><small>Add new image records or register a patient</small></span><b>@include('partials.eye-icon')</b></a>
                <a href="{{ route('admin.doctors') }}"><span class="stemi-action-circle">♧</span><span><strong>Manage Doctors</strong><small>Manage clinical access and doctor accounts</small></span><b>@include('partials.eye-icon')</b></a>
                <a href="{{ route('admin.consultants') }}"><span class="stemi-action-circle">♧</span><span><strong>Manage Consultants</strong><small>Manage consultant user accounts</small></span><b>@include('partials.eye-icon')</b></a>
            @elseif($user->isDoctor())
                <a href="{{ route('doctor.patients') }}"><span class="stemi-action-circle">▧</span><span><strong>Review ECG Images</strong><small>Examine ECG reports and start an assessment</small></span><b>@include('partials.eye-icon')</b></a>
                <a href="{{ route('doctor.my-patients') }}"><span class="stemi-action-circle">♙</span><span><strong>My Patients</strong><small>Continue your active examinations</small></span><b>@include('partials.eye-icon')</b></a>
            @else
                <a href="{{ route('consultant.patients') }}"><span class="stemi-action-circle">▧</span><span><strong>Review Patient Records</strong><small>View ECG images and clinical interpretations</small></span><b>@include('partials.eye-icon')</b></a>
                <a href="{{ route('consultant.reports') }}"><span class="stemi-action-circle">▤</span><span><strong>Examination Reports</strong><small>Review submitted clinical findings</small></span><b>@include('partials.eye-icon')</b></a>
            @endif
            <a href="{{ route($path.'.reports') }}"><span class="stemi-action-circle">▥</span><span><strong>View Reports</strong><small>Browse documented ECG interpretation findings</small></span><b>@include('partials.eye-icon')</b></a>
        </div>
    </section>
</div>
<section class="stemi-card stemi-recent-card"><div class="stemi-card-heading"><span class="stemi-section-icon">♡</span><div><h2>Recent ECG Interpretations</h2><p>Clinical notes entered by authorized doctors</p></div></div>
    <div class="stemi-table-scroll"><table class="stemi-table"><thead><tr><th>Patient</th><th>ECG Category</th><th>Assessment</th><th>Doctor</th><th>Updated</th></tr></thead><tbody>
    @forelse($recentInterpretations as $finding)
    <tr><td>{{ $finding->image->patient->subject_id ?? '—' }}</td><td>{{ $finding->category }}</td><td><span class="stemi-pill {{ $finding->assessment === 'Urgent Review' ? 'needs_review' : 'completed' }}">{{ $finding->assessment }}</span></td><td>{{ $finding->doctor->name ?? '—' }}</td><td>{{ $finding->updated_at->format('d M Y') }}</td></tr>
    @empty <tr><td colspan="5" class="stemi-empty">No ECG interpretations have been recorded.</td></tr> @endforelse
    </tbody></table></div>
</section>
@endsection

