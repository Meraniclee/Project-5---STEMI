@extends('layouts.heartscope')
@section('title', $patient->exists ? 'Edit Patient' : 'Add Patient')
@section('content')
@php $rolePath = auth()->user()->role; $editing = $patient->exists; @endphp
<div class="stemi-page-banner"><div><div class="stemi-crumb"><a href="{{ route($rolePath.'.patients') }}">Patients</a> <span>›</span> {{ $editing ? 'Edit Patient' : 'Add Patient' }}</div><div class="stemi-eyebrow">PATIENT INFORMATION</div><h1>{{ $editing ? 'Edit Patient '.$patient->subject_id : 'Add Patient' }}</h1><p>{{ $editing ? 'Update the patient details used for this examination.' : 'Create a patient record, then attach ECG images when available.' }}</p></div></div>
<section class="stemi-card stemi-form-card">
    <div class="stemi-card-heading"><span class="stemi-section-icon">♙</span><div><h2>Patient Details</h2><p>Case status is updated by the examination workflow.</p></div></div>
    <form class="stemi-upload-form" action="{{ $editing ? route($rolePath.'.patients.update', $patient) : route('admin.patients.store') }}" method="POST">
        @csrf
        @if($editing) @method('PUT') @endif
        <label for="subject_id">Patient ID</label>
        <input id="subject_id" name="subject_id" required maxlength="100" value="{{ old('subject_id', $patient->subject_id) }}" autocomplete="off">
        @error('subject_id')<p role="alert">{{ $message }}</p>@enderror
        <div class="stemi-field-row">
            <div><label for="age_category">Age Category</label><select id="age_category" name="age_category"><option value="">Not specified</option>@foreach(['Neonate','Infant','Child','Adolescent','Young Adult','Adult','Older Adult'] as $age)<option @selected(old('age_category', $patient->age_category) === $age)>{{ $age }}</option>@endforeach</select>@error('age_category')<p role="alert">{{ $message }}</p>@enderror</div>
            <div><label for="sex">Sex</label><select id="sex" name="sex"><option value="">Not specified</option>@foreach(['Male','Female'] as $sex)<option @selected(old('sex', $patient->sex) === $sex)>{{ $sex }}</option>@endforeach</select>@error('sex')<p role="alert">{{ $message }}</p>@enderror</div>
        </div>
        <div class="stemi-form-actions"><a href="{{ $editing ? route($rolePath.'.patients.show', $patient) : route($rolePath.'.patients') }}">Cancel</a><button class="stemi-primary-btn" type="submit">{{ $editing ? 'Save Patient Information' : 'Add Patient' }}</button></div>
    </form>
</section>
@endsection
