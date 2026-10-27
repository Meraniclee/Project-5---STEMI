@extends('layouts.heartscope')
@section('title', 'Examination Record - '.$patient->subject_id)
@push('final-styles')<link rel="stylesheet" href="{{ asset('assets/css/stemi-reports.css') }}">@endpush
@section('content')
@php $rolePath = auth()->user()->role; @endphp
<div class="stemi-page-banner report-screen-only"><div><div class="stemi-crumb"><a href="{{ route($rolePath.'.reports') }}">Reports</a> <span aria-hidden="true">/</span> <a href="{{ route($rolePath.'.reports') }}">Patient's Examination Record</a> <span aria-hidden="true">/</span> {{ $patient->subject_id }}</div><div class="stemi-eyebrow">SAVED EXAMINATION FINDINGS</div><h1>Patient {{ $patient->subject_id }}</h1><p>Read every saved answer, grouped by examination and ECG image.</p></div></div>
<div class="report-toolbar report-screen-only">
    <a class="stemi-primary-btn report-secondary-btn" href="{{ route($rolePath.'.reports') }}">Patient's Examination Record @include('partials.eye-icon')</a>
    <div class="report-export-actions" aria-label="Export examination report">
        <button type="button" class="stemi-primary-btn report-secondary-btn" data-report-print>Print</button>
        <button type="button" class="stemi-primary-btn" data-report-export="xlsx">Download Excel (.xlsx)</button>
        <button type="button" class="stemi-primary-btn report-secondary-btn" data-report-export="png">Download PNG</button>
        <button type="button" class="stemi-primary-btn report-secondary-btn" data-report-export="pdf">Download PDF</button>
    </div>
    <p class="report-export-hint">All exports include the saved ECG findings. Excel downloads an editable workbook, PDF includes every report page, and PNG pages download in a ZIP file.</p>
    <p id="reportExportStatus" class="report-export-status" role="status" aria-live="polite"></p>
</div>
<article id="examinationReport" class="examination-report">
    <header class="report-header">
        <div><p class="report-kicker">STEMI / EXAMINATION RECORD</p><h1>Patient {{ $report['patient']['label'] }}</h1><p>Patient's Examination Record</p></div>
        <dl class="report-patient-meta"><div><dt>Age category</dt><dd>{{ $report['patient']['age'] }}</dd></div><div><dt>Sex</dt><dd>{{ $report['patient']['sex'] }}</dd></div><div><dt>Saved ECG records</dt><dd>{{ count($report['records']) }}</dd></div><div><dt>Report generated</dt><dd>{{ $report['generatedAt'] }}</dd></div></dl>
    </header>
    <p class="report-explanation">These are the latest saved answers for each examination and ECG image. “Not recorded” marks an unanswered field. Partial records remain available while the doctor continues the examination.</p>
    <nav class="report-record-index report-screen-only" aria-label="Saved ECG records">
        @foreach($report['records'] as $record)<a href="#saved-record-{{ $record['id'] }}">{{ $record['image'] }} · {{ $record['doctor'] }} · Examination {{ $record['examinationId'] ?? 'unavailable' }} @include('partials.eye-icon')</a>@endforeach
    </nav>
    @foreach($report['records'] as $record)
        <section class="report-record" id="saved-record-{{ $record['id'] }}" aria-labelledby="record-title-{{ $record['id'] }}">
            <header class="report-record-header">
                <div><p class="report-kicker">EXAMINATION {{ $record['examinationId'] ?? 'UNAVAILABLE' }} / SAVED ECG {{ $loop->iteration }}</p><h2 id="record-title-{{ $record['id'] }}">{{ $record['image'] }}</h2><p>Examined by {{ $record['doctor'] }}</p></div>
                <dl><div><dt>Last saved</dt><dd>{{ $record['updatedAt'] }}</dd></div><div><dt>Examination status</dt><dd>{{ $record['examinationStatus'] }}</dd></div><div><dt>Findings status</dt><dd>{{ $record['status'] }}</dd></div></dl>
            </header>
            <div class="report-section-actions report-screen-only" hidden>
                <button type="button" data-report-sections="expand">Expand all sections</button>
                <button type="button" data-report-sections="collapse">Collapse all sections</button>
            </div>
            <div class="report-sections">
                @foreach($record['sections'] as $section)
                    @php $sectionId = 'report-section-'.$record['id'].'-'.$loop->index; @endphp
                    <section class="report-findings-section {{ str_starts_with($section['title'], 'Lead Findings') ? 'report-lead-section' : 'report-wide-section' }}">
                        <h3><button type="button" class="report-section-toggle" aria-expanded="true" aria-controls="{{ $sectionId }}">
                            <span>{{ $section['title'] }}</span>
                            <span class="report-section-indicator report-screen-only" hidden>@include('partials.eye-icon') <span data-report-toggle-label>Collapse</span></span>
                        </button></h3>
                        <div id="{{ $sectionId }}" class="report-section-content">
                        @if($section['title'] === 'Overall ECG Interpretation')<p class="report-subtitle">Overall Diagnosis</p>@endif
                        <dl class="report-answers">
                            @foreach($section['rows'] as $row)<div class="{{ $row['value'] === 'Not recorded' ? 'report-unanswered' : 'report-answered' }}"><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>@endforeach
                        </dl>
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    @endforeach
    <footer class="report-footer">STEMI · Patient {{ $report['patient']['label'] }} · {{ $report['generatedAt'] }}</footer>
</article>
<script type="application/json" id="examinationReportData">{!! json_encode($report, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/stemi-report-sections.js') }}" defer></script>
<script src="{{ asset('assets/vendor/pdf-lib.min.js') }}" defer></script>
<script src="{{ asset('assets/vendor/jszip.min.js') }}" defer></script>
<script src="{{ asset('assets/js/stemi-report-export.js') }}" defer></script>
@endpush
