@extends('layouts.heartscope')
@section('title', 'Doctors')
@section('content')
<div class="stemi-page-banner"><div><div class="stemi-crumb">Consultant Portal <span>›</span> Doctors</div><div class="stemi-eyebrow">DOCTOR PREVIEW</div><h1>Doctors</h1><p>Review doctor profiles, assigned patients and examination activity.</p></div></div>
<section class="stemi-card">
    <div class="stemi-card-heading"><span class="stemi-section-icon">♧</span><div><h2>Doctor Directory</h2><p>{{ $doctors->total() }} doctors · Preview access</p></div></div>
    <form class="stemi-filter" method="GET"><input type="search" aria-label="Search doctors" name="q" value="{{ $search }}" placeholder="Search name or specialization"><button class="stemi-primary-btn" type="submit">Search</button><a href="{{ route('consultant.doctors') }}">Clear</a></form>
    <div class="stemi-table-scroll"><table class="stemi-table"><thead><tr><th>Doctor</th><th>Specialization</th><th>Status</th><th>Examinations</th><th>Completed</th><th>Action</th></tr></thead><tbody>
    @forelse($doctors as $doctor)<tr><td><strong>{{ $doctor->name }}</strong></td><td>{{ $doctor->specialization ?? '—' }}</td><td>{{ ucfirst($doctor->status) }}</td><td>{{ $doctor->examinations_count }}</td><td>{{ $doctor->completed_examinations_count }}</td><td><a href="{{ route('consultant.doctors.show', $doctor) }}">View Doctor @include('partials.eye-icon')</a></td></tr>@empty<tr><td colspan="6" class="stemi-empty">No doctors match your search.</td></tr>@endforelse
    </tbody></table></div><div class="stemi-pagination">{{ $doctors->links() }}</div>
</section>
@endsection

