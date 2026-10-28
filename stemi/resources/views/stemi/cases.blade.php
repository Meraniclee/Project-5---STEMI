@extends('layouts.heartscope')
@section('title', auth()->user()->isDoctor() ? 'My Cases' : 'Case Status')
@section('content')
@php $user = auth()->user(); $role=$user->role; @endphp
<div class="stemi-page-banner"><div><div class="stemi-crumb">{{ ucfirst($role) }} <span>›</span> Case Status</div><div class="stemi-eyebrow">EXAMINATION TRACKING</div><h1>{{ $user->isDoctor() ? 'My Cases' : 'Case Status' }}</h1><p>Track ECG examination assignments, progress and completion.</p></div></div>
<section class="stemi-card"><div class="stemi-card-heading"><span class="stemi-section-icon">▤</span><div><h2>Clinical Examinations</h2><p>{{ $examinations->total() }} recorded examinations</p></div></div>
<div class="stemi-table-scroll"><table class="stemi-table"><thead><tr><th>Patient ID</th><th>Doctor</th><th>ECG Images</th><th>Examination Status</th><th>Started</th><th>Completed</th><th></th></tr></thead><tbody>
@forelse($examinations as $exam)<tr><td><strong>{{ $exam->patient->subject_id ?? '—' }}</strong></td><td>{{ $exam->doctor->name ?? '—' }}</td><td>{{ $exam->patient->ecg_images_count ?? 0 }}</td><td><span class="stemi-pill {{ $exam->status === 'completed' ? 'completed' : 'in_progress' }}">{{ ucwords(str_replace('_',' ',$exam->status)) }}</span></td><td>{{ $exam->started_at?->format('d M Y') ?? '—' }}</td><td>{{ $exam->completed_at?->format('d M Y') ?? '—' }}</td><td>@if($exam->patient)<a href="{{ route($role.'.patients.show', $exam->patient) }}">View @include('partials.eye-icon')</a>@endif</td></tr>
@empty<tr><td colspan="7" class="stemi-empty">No examinations have been started.</td></tr>@endforelse</tbody></table></div><div class="stemi-pagination">{{ $examinations->links() }}</div></section>
@endsection

