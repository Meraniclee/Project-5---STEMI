@php
    $schema = \App\Support\EcgAnnotationSchema::class;
    $reviewData = $allFindings->map(fn($f) => [
        'id' => $f->id, 'image_id' => $f->ecg_image_id, 'examination_id' => $f->examination_id,
        'doctor' => $f->doctor?->name ?? 'Doctor', 'updated' => $f->updated_at->format('d M Y H:i'),
        'lead_findings' => $f->lead_findings, 'overall' => $f->overall, 'stemi' => $f->stemi,
        'extraction' => $f->extraction, 'notes' => $f->notes, 'annotation_complete' => $f->annotation_complete,
        'legacy' => $f->overall ? null : trim($f->category.' · '.$f->subcategory.' · '.$f->rhythm.' · '.$f->assessment),
    ])->values();
    $annotationData = [
        'editable' => (bool)$activeExam, 'examinationId' => $exam?->id, 'reviews' => $reviewData,
        'leads' => $schema::leads(), 'old' => session()->hasOldInput() ? session()->getOldInput() : null,
        'selectedImage' => old('ecg_image_id', request('image', $selected->id)),
    ];
@endphp
<div class="annotation-toolbar stemi-card">
    <div><span class="stemi-eyebrow">ECG EXAMINATION</span><h2>{{ $activeExam ? 'Document this ECG' : 'ECG review' }}</h2><p id="annotationReviewStatus" role="status">Select an ECG image to review its leads.</p>
        @if($activeExam)<p class="annotation-autosave"><strong id="autosaveStatus" role="status">All changes saved automatically</strong> <button type="button" class="stemi-secondary-btn" data-retry-autosave hidden>Retry saving</button></p><p>Every change is saved automatically. You can leave leads unfinished and return later.</p>@endif
        <div class="annotation-answer-legend"><span><i class="legend-answered" aria-hidden="true"></i> Soft blue: answer entered</span><span><i class="legend-unanswered" aria-hidden="true"></i> Default: no answer yet</span></div>
        <p>Entered answers stay blue when you return.@if($activeExam) Colour indicates an answer, while the save status confirms saving.@endif</p>
    </div>
    <div class="annotation-toolbar-actions">
        @if(auth()->user()->isAdmin() || $activeExam)<a class="stemi-secondary-btn" href="{{ route($rolePath.'.patients.edit', $patient) }}">Edit patient information</a>@endif
        @if(auth()->user()->isAdmin())<a class="stemi-primary-btn" href="{{ route('admin.ecg-images.create', ['patient_id' => $patient->id]) }}">Add ECG images</a>@endif
        @if($doctor && !$activeExam)
        <form action="{{ route('doctor.patients.start', $patient) }}" method="POST">@csrf<button class="stemi-primary-btn">{{ $exam ? 'Start new examination' : 'Start examination' }}</button></form>
        @endif
    </div>
</div>
@if(!$activeExam)<div class="annotation-history"><label for="reviewSelector">Recorded interpretation</label><select id="reviewSelector"></select><p id="legacyInterpretation" hidden></p></div>@endif
<noscript><div class="stemi-alert error">Enable JavaScript to extract ECG leads and use the annotation workspace.</div></noscript>
<form id="ecgAnnotationForm" class="annotation-workspace" action="{{ $activeExam ? route('doctor.interpretations.store', $patient) : '#' }}" method="POST" data-editable="{{ $activeExam ? 'true' : 'false' }}">
    @csrf
    <input type="hidden" name="ecg_image_id" id="selectedEcgImageId" value="{{ $annotationData['selectedImage'] }}">
    <div id="extractionInputs"></div>
    <section class="stemi-card annotation-extraction">
        <div class="stemi-card-heading"><span class="stemi-section-icon">▦</span><div><h2>Extracted 12 Leads</h2><p>Choose a lead to examine its image and document findings.</p></div><span class="annotation-badge" id="cropStatus">Preparing lead images</span></div>
        <p class="annotation-help">Layout: I / aVR / V1 / V4, II / aVL / V2 / V5, III / aVF / V3 / V6. Check each crop against the original ECG; the rhythm strips are excluded.</p>
        <div class="annotation-lead-legend"><span><i class="legend-finished"></i> Examined</span><span><i class="legend-unfinished"></i> Unfinished examination</span></div>
        <div id="extractionError" class="stemi-alert error" role="alert" hidden></div>
        <details id="cropEditor" class="annotation-crop-editor">
            <summary>Inspect and adjust extraction boundaries</summary>
            <p>For the illustrated 3 × 4 layout only. Drag over the first three rows to set the crop, or adjust the percentages below. Confirm that the labels match before completing the examination.</p>
            <canvas id="cropOverview" aria-label="Original ECG with the twelve crop boundaries"></canvas>
            @if($activeExam)
            <div class="crop-controls">
            @foreach(['left' => 'Left', 'top' => 'Top', 'width' => 'Width', 'height' => 'Height', 'col0' => 'Column divider 1', 'col1' => 'Column divider 2', 'col2' => 'Column divider 3', 'row0' => 'Row divider 1', 'row1' => 'Row divider 2'] as $key => $label)
                <label>{{ $label }} (%)<input type="number" data-crop-control="{{ $key }}" min="0" max="100" step="any"></label>
            @endforeach
            </div>
            <div class="annotation-toolbar-actions"><button type="button" class="stemi-secondary-btn" id="redetectCrops">Detect again</button><button type="button" class="stemi-primary-btn" id="confirmCrops">Confirm all 12 lead crops</button></div>
            @endif
        </details>
        <div class="annotation-lead-grid" role="tablist" aria-label="ECG leads">
        @foreach([['I','aVR','V1','V4'],['II','aVL','V2','V5'],['III','aVF','V3','V6']] as $row)
        @foreach($row as $lead)
            <button type="button" class="annotation-lead lead-unfinished" id="lead-tab-{{ $lead }}" data-lead="{{ $lead }}" role="tab" aria-controls="lead-panel-{{ $lead }}" aria-selected="{{ $lead === 'I' ? 'true' : 'false' }}" tabindex="{{ $lead === 'I' ? '0' : '-1' }}"><span><strong>{{ $lead }}</strong><small data-lead-progress="{{ $lead }}">0 / 9 findings</small></span><canvas data-lead-canvas="{{ $lead }}" aria-label="Extracted lead {{ $lead }}"></canvas><span class="annotation-lead-state" data-lead-state="{{ $lead }}">Unfinished</span></button>
        @endforeach
        @endforeach
        </div>
    </section>
    <section class="stemi-card annotation-findings">
        <div class="stemi-card-heading"><span class="stemi-section-icon">⌁</span><div><h2>Lead Findings <span id="activeLeadTitle">— I</span></h2><p id="leadCompletionSummary">Select a finding for each field. Measurements may be left blank when not measurable.</p></div></div>
        <div class="annotation-lead-detail"><canvas id="selectedLeadCanvas" aria-label="Enlarged selected ECG lead"></canvas></div>
        <fieldset class="annotation-editable" @disabled(!$activeExam)>
        @foreach($schema::leads() as $lead)
            <div id="lead-panel-{{ $lead }}" class="annotation-field-grid" role="tabpanel" aria-labelledby="lead-tab-{{ $lead }}" @if($lead !== 'I') hidden @endif>
            @foreach($schema::leadFields() as $key => $definition)
                @include('stemi.annotation-field', ['fieldName' => 'lead_findings['.$lead.']['.$key.']', 'definition' => $definition])
            @endforeach
            </div>
        @endforeach
        </fieldset>
        <div class="annotation-lead-navigation"><button type="button" class="stemi-secondary-btn" id="previousLead">@include('partials.eye-icon') Previous lead</button><button type="button" class="stemi-secondary-btn" id="nextLead">Next lead @include('partials.eye-icon')</button></div>
    </section>
    <section class="stemi-card">
        <div class="stemi-card-heading"><span class="stemi-section-icon">♡</span><div><h2>Overall ECG Interpretation</h2><p>Overall Diagnosis</p></div></div>
        <fieldset class="annotation-editable annotation-field-grid" @disabled(!$activeExam)>
        @foreach($schema::overallFields() as $key => $definition)
            @include('stemi.annotation-field', ['fieldName' => 'overall['.$key.']', 'definition' => $definition])
        @endforeach
        </fieldset>
        <p class="annotation-help">MI localisation allows multiple regions or an indeterminate label. NSTEMI interpretation is clinician-entered and requires clinical correlation.</p>
    </section>
    <section class="stemi-card">
        <div class="stemi-card-heading"><span class="stemi-section-icon">✚</span><div><h2>STEMI Specific Annotation</h2><p>Record the ECG findings supporting the doctor's interpretation.</p></div></div>
        <p class="annotation-help">New or presumed-new LBBB: where clinically established. Acute coronary occlusion suspected: where within the reviewing clinician's scope.</p>
        <fieldset class="annotation-editable annotation-field-grid" @disabled(!$activeExam)>
        @foreach($schema::stemiFields() as $key => $definition)
            @if($key !== 'elevation_measurements')
            @include('stemi.annotation-field', ['fieldName' => 'stemi['.$key.']', 'definition' => $definition])
            @endif
        @endforeach
        </fieldset>
        <fieldset class="annotation-editable" @disabled(!$activeExam)><legend class="annotation-measurement-title">ST elevation measurement (mm per relevant lead, if measurable)</legend>
            <p class="annotation-help">Select leads with ST elevation above to enter measurements. These use the same measurements as Lead Findings.</p>
            <div id="stemiMeasurements" class="annotation-measurement-grid">
            @foreach($schema::leads() as $lead)
                <label data-measurement-lead="{{ $lead }}" hidden>{{ $lead }} <span class="annotation-answer-state" data-answer-state>Not answered</span><div class="annotation-number"><input type="number" name="stemi[elevation_measurements][{{ $lead }}]" min="0" max="100" step="any" placeholder="Not measured"><span>mm</span></div></label>
            @endforeach
            </div>
        </fieldset>
    </section>
    <section class="stemi-card annotation-save-section">
        <fieldset class="annotation-editable" @disabled(!$activeExam)><label for="annotationNotes">Doctor's notes</label><textarea id="annotationNotes" name="notes" rows="4" maxlength="10000" placeholder="Additional observations and clinical correlation..."></textarea></fieldset>
        @if($activeExam)
        <div class="annotation-save-bar"><div><strong id="draftStatus" role="status">All changes saved automatically</strong><p>Your progress is saved even when only one finding is entered. Complete the examination when every ECG review is finished.</p></div><div class="annotation-toolbar-actions"><button class="stemi-primary-btn" type="submit" id="completeExamination" name="complete" value="1">Complete examination</button></div></div>
        @else<p class="annotation-help">Read-only preview. Findings are recorded by the examining doctor.</p>@endif
        <p class="annotation-help">Lead extraction creates image crops. All findings and measurements are entered by the doctor; no diagnosis is generated automatically.</p>
    </section>
</form>
<script type="application/json" id="annotationData">{!! json_encode($annotationData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@push('final-styles')<link rel="stylesheet" href="{{ asset('assets/css/stemi-annotations.css') }}">@endpush
@push('scripts')
<script src="{{ asset('assets/js/ecg-lead-extractor.js') }}" defer></script>
<script src="{{ asset('assets/js/stemi-autosave.js') }}" defer></script>
<script src="{{ asset('assets/js/stemi-annotations.js') }}" defer></script>
@endpush

