@extends('layouts.heartscope')
@section('title', 'ECG Image Upload')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/stemi-ecg-upload.css') }}">
@endpush

@section('content')
<div class="stemi-page-banner">
    <div>
        <div class="stemi-crumb">Administrator <span>›</span> ECG Upload</div>
        <div class="stemi-eyebrow">IMAGE MANAGEMENT</div>
        <h1>Upload ECG Images</h1>
        <p>Register ECG report images securely to an existing or new patient record.</p>
    </div>
</div>

<section class="stemi-card stemi-form-card">
    <div class="stemi-card-heading">
        <span class="stemi-section-icon">⇧</span>
        <div>
            <h2>New ECG Image Submission</h2>
            <p>Supported formats: JPG, JPEG, PNG — up to 10 MB per file, maximum 10 files</p>
        </div>
    </div>

    <form action="{{ route('admin.ecg-images.store') }}" method="POST" enctype="multipart/form-data" class="stemi-upload-form">
        @csrf

        <div class="stemi-upload-columns">
            <div>
                <h3>Existing Patient</h3>
                <label for="patient_id">Select Patient</label>
                <select name="patient_id" id="patient_id">
                    <option value="">Choose existing patient (optional)</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" @selected(old('patient_id', request('patient_id')) == $patient->id)>{{ $patient->subject_id }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <h3>Or Register New Patient</h3>
                <label for="new_subject_id">New Patient ID</label>
                <input id="new_subject_id" name="new_subject_id" value="{{ old('new_subject_id') }}" placeholder="e.g. 14998">

                <div class="stemi-field-row">
                    <div>
                        <label for="age_category">Age Category</label>
                        <select name="age_category" id="age_category">
                            <option value="">Not specified</option>
                            @foreach(['Neonate','Infant','Child','Adolescent','Young Adult','Adult','Older Adult'] as $age)
                                <option @selected(old('age_category') === $age)>{{ $age }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="sex">Sex</label>
                        <select name="sex" id="sex">
                            <option value="">Not specified</option>
                            @foreach(['Male','Female'] as $sex)
                                <option @selected(old('sex') === $sex)>{{ $sex }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <label for="images">ECG Image Files</label>
        <div class="stemi-file-area">
            <span>▧</span>
            <strong>Select ECG reports to upload</strong>
            <p>Original files will be held in private, authorization-controlled storage.</p>

            <div class="stemi-upload-controls">
                <input type="file" name="images[]" id="images" accept="image/jpeg,image/png" required multiple>
                <button class="stemi-primary-btn" type="submit">Upload ECG Images</button>
            </div>
        </div>

        <div class="stemi-form-actions stemi-upload-actions">
            <a href="{{ route('admin.patients') }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
