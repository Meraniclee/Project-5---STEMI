@extends('layouts.heartscope')
@section('title', 'Patient '.$patient->subject_id)
@section('content')
@php
    $rolePath = auth()->user()->role;
    $doctor = auth()->user()->isDoctor();
    $activeExam = $doctor && $exam && in_array($exam->status, ['draft', 'in_progress'], true);
    $images = $patient->ecgImages;
    $selected = $images->first();
    $age = $patient->age_category ?? 'Not specified';
    $status = ucwords(str_replace('_', ' ', $patient->case_status));
@endphp
<section class="stemi-page-banner stemi-patient-banner">
    <div><div class="stemi-crumb"><a href="{{ route($rolePath.'.patients') }}">@include('partials.eye-icon') {{ $doctor ? 'My Patients' : 'Patients' }}</a> <span>›</span> Patient {{ $patient->subject_id }}</div><div class="stemi-eyebrow">PATIENT RECORD</div><h1>Patient {{ $patient->subject_id }}</h1><p>View patient information, ECG images and examination findings.</p></div>
</section>
<div class="stemi-patient-kpis">
    <div><span>Patient ID</span><strong>{{ $patient->subject_id }}</strong></div>
    <div><span>Age Category</span><strong>{{ $age }}</strong></div>
    <div><span>Sex</span><strong>{{ $patient->sex ?? 'Not specified' }}</strong></div>
    <div><span>Total ECG Images</span><strong>{{ $images->count() }}</strong></div>
    <div><span>Latest ECG Date</span><strong>{{ $selected?->created_at?->format('d M Y') ?? '—' }}</strong></div>
    <div><span>Case Status</span><strong><span class="stemi-pill {{ $patient->case_status }}">{{ $status }}</span></strong></div>
</div>
<section class="stemi-card stemi-ecg-workspace">
    <div class="stemi-card-heading"><span class="stemi-section-icon">▧</span><div><h2>ECG Images</h2><p>View uploaded ECG images for this patient. Select any image to view its details.</p></div></div>
    @if($images->isEmpty())
        <div class="stemi-empty-illustration"><span>▧</span><h3>No ECG images uploaded</h3><p>Images must be added by an administrator before the examination can begin.</p>
        @if(auth()->user()->isAdmin()) <a class="stemi-primary-btn" href="{{ route('admin.ecg-images.create') }}">Upload ECG Images</a> @endif</div>
    @else
    <div class="stemi-ecg-columns">
        <div class="stemi-ecg-thumbnails" aria-label="ECG image thumbnails">
            <div class="stemi-thumbnail-grid">
            @foreach($images as $image)
            @php $finding=$myFindings->get($image->id); @endphp
            <button type="button" class="stemi-thumbnail {{ $loop->first ? 'selected' : '' }}" data-ecg-image
                data-image-id="{{ $image->id }}" data-src="{{ route('ecg-images.file', $image) }}"
                data-name="{{ $image->original_name }}" data-date="{{ $image->created_at->format('d M Y H:i') }}"
                data-type="{{ strtoupper(str_replace('image/', '', $image->mime_type)) }}"
                data-size="{{ number_format($image->file_size / 1048576, 2) }} MB"
                data-resolution="{{ ($image->width && $image->height) ? $image->width.' × '.$image->height : 'Unknown' }}"
                data-category="{{ $finding?->category ?? '' }}" data-subcategory="{{ $finding?->subcategory ?? '' }}"
                data-rhythm="{{ $finding?->rhythm ?? '' }}" data-assessment="{{ $finding?->assessment ?? '' }}"
                data-notes="{{ $finding?->notes ?? '' }}"
                aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                <span class="stemi-thumbnail-image"><img src="{{ route('ecg-images.file', $image) }}" alt="ECG report thumbnail {{ $image->original_name }}" loading="lazy"></span>
                <span class="stemi-thumbnail-name">{{ $image->original_name }}</span><small>{{ $image->created_at->format('d M Y H:i') }}</small>
            </button>
            @endforeach
            </div>
        </div>
        <div class="stemi-ecg-preview">
            <div class="stemi-ecg-preview-photo"><img id="ecgPreviewImage" src="{{ route('ecg-images.file', $selected) }}" alt="Selected ECG report">
                <button type="button" id="expandEcg" class="stemi-expand" aria-label="Expand selected ECG image" title="Enlarge ECG image">⛶</button>
            </div>
            <div class="stemi-preview-copy"><strong id="ecgPreviewName">{{ $selected->original_name }}</strong><small id="ecgPreviewDate">{{ $selected->created_at->format('d M Y H:i') }}</small></div>
        </div>
        <aside class="stemi-ecg-info"><div class="stemi-info-heading"><span class="stemi-section-icon">▤</span><div><h3>Image Information</h3><p>Details of the selected ECG image.</p></div></div>
            <dl class="stemi-info-list"><div><dt>File Name</dt><dd id="infoFileName">{{ $selected->original_name }}</dd></div>
            <div><dt>Date Uploaded</dt><dd id="infoDate">{{ $selected->created_at->format('d M Y H:i') }}</dd></div>
            <div><dt>Image Format</dt><dd id="infoType">{{ strtoupper(str_replace('image/', '', $selected->mime_type)) }}</dd></div>
            <div><dt>Image Size</dt><dd id="infoSize">{{ number_format($selected->file_size / 1048576, 2) }} MB</dd></div>
            <div><dt>Dimensions</dt><dd id="infoResolution">{{ $selected->width && $selected->height ? $selected->width.' × '.$selected->height : 'Unknown' }}</dd></div>
            <div><dt>Storage Status</dt><dd><span class="stemi-pill completed">Protected</span></dd></div>
            <div><dt>Review Status</dt><dd id="infoReview">{{ $myFindings->has($selected->id) ? 'Documented' : 'Not documented' }}</dd></div></dl>
        </aside>
    </div>
    @endif
</section>
@if($images->isNotEmpty())
    @include('stemi.annotation-workspace')
@elseif(auth()->user()->isAdmin())
    <a class="stemi-secondary-btn" href="{{ route('admin.patients.edit', $patient) }}">Edit patient information</a>
@endif
@if($images->isNotEmpty())
<div id="ecgModal" class="stemi-image-modal" role="dialog" aria-modal="true" aria-label="Expanded ECG image" hidden><div class="stemi-modal-head"><span id="ecgModalTitle">ECG Image</span><div><button type="button" id="zoomOutEcg" aria-label="Zoom out">−</button><button type="button" id="zoomResetEcg">100%</button><button type="button" id="zoomInEcg" aria-label="Zoom in">+</button><button type="button" id="closeEcgModal" aria-label="Close ECG image">✕</button></div></div><div class="stemi-modal-scroll"><img id="ecgModalImage" src="{{ route('ecg-images.file', $selected) }}" alt="Expanded ECG report"></div></div>
@endif
@endsection


