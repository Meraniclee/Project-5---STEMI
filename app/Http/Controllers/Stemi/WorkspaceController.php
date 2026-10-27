<?php

namespace App\Http\Controllers\Stemi;

use App\Http\Controllers\Controller;
use App\Models\EcgImage;
use App\Models\EcgInterpretation;
use App\Models\Examination;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkspaceController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $stats = [
            ['label' => 'Total Patients', 'value' => Patient::count(), 'icon' => 'patients'],
            ['label' => 'ECG Images', 'value' => EcgImage::count(), 'icon' => 'images'],
            ['label' => 'Completed Cases', 'value' => Patient::where('case_status', 'completed')->count(), 'icon' => 'complete'],
            ['label' => 'Needs Review', 'value' => Patient::where('case_status', 'needs_review')->count(), 'icon' => 'review'],
        ];
        if ($user->isAdmin()) {
            $stats[2] = ['label' => 'Active Doctors', 'value' => User::where('role','doctor')->where('status','active')->count(), 'icon' => 'complete'];
            $stats[3] = ['label' => 'Consultants', 'value' => User::where('role','consultant')->where('status','active')->count(), 'icon' => 'review'];
        } elseif ($user->isDoctor()) {
            $stats[2] = ['label' => 'My Cases', 'value' => Examination::where('doctor_id', $user->id)->count(), 'icon' => 'complete'];
        }
        $patients = Patient::query()->withCount('ecgImages')->whereHas('ecgImages')->latest('updated_at')->limit(6)->get();
        $recentInterpretations = EcgInterpretation::query()->with(['image.patient', 'doctor'])
            ->when($user->isDoctor(), fn ($q) => $q->where('doctor_id', $user->id))
            ->latest()->limit(5)->get();
        return view('stemi.dashboard', compact('stats', 'patients', 'recentInterpretations'));
    }

    public function patients(Request $request): View
    {
        $search = trim((string) $request->query('q',''));
        $scope = $request->routeIs('doctor.my-patients') ? 'mine' : 'all';
        $patients = Patient::query()->withCount(['ecgImages', 'examinations'])
            ->with([
                'examinedExaminations' => fn ($query) => $query->select('id', 'patient_id', 'doctor_id')->latest('id'),
                'examinedExaminations.doctor:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->where('subject_id', 'like', '%' . $search . '%'))
            ->when($scope === 'mine', fn ($q) => $q->whereHas('examinations', fn ($e) => $e->where('doctor_id', $request->user()->id)))
            ->latest('updated_at')->paginate(12)->withQueryString();
        return view('stemi.patients', compact('patients', 'search', 'scope'));
    }

    public function patient(Request $request, Patient $patient): View
    {
        $patient->load(['ecgImages' => fn ($q) => $q->latest()->orderByDesc('id'), 'examinations' => fn ($q) => $q->latest()->orderByDesc('id')]);
        $user = $request->user();
        $exam = $user->isDoctor()
            ? $patient->examinations->firstWhere('doctor_id', $user->id)
            : $patient->examinations->first();
        $reviewDoctor = $request->route('user');
        $allFindings = EcgInterpretation::whereIn('ecg_image_id', $patient->ecgImages->pluck('id'))
            ->when($reviewDoctor instanceof User, fn ($q) => $q->where('doctor_id', $reviewDoctor->id))
            ->with(['doctor', 'image'])->latest()->orderByDesc('id')->get();
        $myFindings = $user->isDoctor() && $exam
            ? $allFindings->where('examination_id', $exam->id)->keyBy('ecg_image_id')
            : collect();
        return view('stemi.patient-record', compact('patient', 'exam', 'allFindings', 'myFindings'));
    }

    public function patientForDoctor(Request $request, User $user, Patient $patient): View
    {
        abort_unless($user->isDoctor(), 404);
        abort_unless($patient->examinations()->where('doctor_id', $user->id)->exists(), 404);
        return $this->patient($request, $patient);
    }

    public function uploadForm(Request $request): View
    {
        return view('stemi.upload', [
            'patients' => Patient::orderBy('subject_id')->get(['id', 'subject_id']),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['nullable', 'required_without:new_subject_id', 'prohibits:new_subject_id', 'integer', 'exists:patients,id'],
            'new_subject_id' => ['nullable', 'required_without:patient_id', 'prohibits:patient_id', 'string', 'max:100', 'unique:patients,subject_id'],
            'age_category' => ['nullable', Rule::in(['Neonate','Infant','Child','Adolescent','Young Adult','Adult','Older Adult'])],
            'sex' => ['nullable', Rule::in(['Male','Female'])],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        $stored = [];
        try {
            $patient = DB::transaction(function () use ($data, $request, &$stored) {
                $patient = !empty($data['patient_id'])
                    ? Patient::whereKey($data['patient_id'])->lockForUpdate()->firstOrFail()
                    : Patient::create([
                        'subject_id' => $data['new_subject_id'],
                        'age_category' => $data['age_category'] ?? null,
                        'sex' => $data['sex'] ?? null,
                        'case_status' => 'pending',
                    ]);
                foreach ($request->file('images') as $file) {
                    $path = $file->store('ecg-images/' . $patient->id, 'local');
                    $stored[] = $path;
                    $dimensions = @getimagesize($file->getRealPath());
                    EcgImage::create([
                        'patient_id' => $patient->id,
                        'uploaded_by' => $request->user()->id,
                        'original_name' => basename($file->getClientOriginalName()),
                        'storage_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'width' => $dimensions[0] ?? null,
                        'height' => $dimensions[1] ?? null,
                    ]);
                }
                if ($patient->case_status === 'completed') {
                    $patient->update(['case_status' => 'pending']);
                } else {
                    $patient->touch();
                }
                return $patient;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $path) Storage::disk('local')->delete($path);
            throw $e;
        }
        return redirect()->route('admin.patients.show', $patient)->with('success', 'ECG images uploaded securely.');
    }

    public function image(Request $request, EcgImage $image): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($image->storage_path), 404);
        return response()->file(Storage::disk('local')->path($image->storage_path), [
            'Content-Type' => $image->mime_type,
            'Content-Disposition' => 'inline; filename="ecg-' . $image->id . '.' . ($image->mime_type === 'image/png' ? 'png' : 'jpg') . '"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function claim(Request $request, Patient $patient): RedirectResponse
    {
        DB::transaction(function () use ($patient, $request) {
            $patient = Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            if (!$patient->ecgImages()->exists()) {
                throw ValidationException::withMessages(['patient' => 'Please upload an ECG image first.']);
            }
            $active = Examination::where('patient_id', $patient->id)
                ->whereIn('status', ['draft', 'in_progress'])->lockForUpdate()->first();
            if ($active && (int)$active->doctor_id !== (int)$request->user()->id) {
                throw ValidationException::withMessages(['patient' => 'This case is currently assigned to another doctor.']);
            }
            if (!$active) {
                Examination::create([
                    'patient_id' => $patient->id, 'doctor_id' => $request->user()->id,
                    'status' => 'in_progress', 'started_at' => now(),
                ]);
            }
            $patient->update(['case_status' => 'in_progress']);
        });
        return back()->with('success', 'Examination started. You can now document ECG findings.');
    }

    public function saveInterpretation(\App\Http\Requests\SaveEcgInterpretationRequest $request, Patient $patient): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $interpretation = DB::transaction(function () use ($request, $patient, $data) {
            // Serialize examination writes for this patient before checking ownership.
            $patient = Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            $exam = Examination::query()->where('patient_id', $patient->id)
                ->where('doctor_id', $request->user()->id)
                ->whereIn('status', ['draft', 'in_progress'])->lockForUpdate()->latest('id')->first();
            abort_unless($exam, 403, 'Start your own examination before saving findings.');

            $primary = data_get($data, 'overall.primary_interpretation');
            $category = match ($primary) {
                'Normal ECG' => 'Normal ECG',
                'Myocardial infarction pattern' => 'Myocardial Infarction (MI)',
                'Ischemic changes without definite MI pattern', 'Other abnormal ECG' => 'Other Abnormality',
                default => 'Inconclusive',
            };
            // Legacy columns remain useful to existing reports. All clinical choices
            // come from the doctor; these flags only summarize their selections.
            $urgent = data_get($data, 'overall.stemi_interpretation') === 'STEMI pattern present'
                || data_get($data, 'overall.mi_evidence') === 'Acute MI suspected'
                || data_get($data, 'stemi.interpretation') === 'Present'
                || data_get($data, 'stemi.occlusion_suspected') === 'Yes';
            $assessment = $urgent ? 'Urgent Review' : match ($category) {
                'Normal ECG' => 'Normal', 'Inconclusive' => 'Inconclusive', default => 'Abnormal',
            };
            $annotationComplete = \Illuminate\Support\Facades\Validator::make(
                $data, \App\Support\EcgAnnotationSchema::annotationRules(true)
            )->passes() && \App\Support\EcgAnnotationSchema::consistencyErrors($data, true) === [];
            $finding = EcgInterpretation::updateOrCreate([
                'ecg_image_id' => $data['ecg_image_id'], 'examination_id' => $exam->id,
            ], [
                'doctor_id' => $request->user()->id,
                'category' => $category,
                'subcategory' => null,
                'rhythm' => 'Undetermined',
                'assessment' => $assessment,
                'notes' => $data['notes'] ?? null,
                'lead_findings' => $data['lead_findings'] ?? [],
                'overall' => $data['overall'] ?? [],
                'stemi' => $data['stemi'] ?? [],
                'extraction' => $data['extraction'] ?? [],
                'annotation_complete' => $annotationComplete,
            ]);
            $complete = (bool) ($data['complete'] ?? false);
            $completeImageCount = EcgInterpretation::where('examination_id', $exam->id)
                ->where('annotation_complete', true)
                ->whereIn('ecg_image_id', $patient->ecgImages()->select('id'))->count();
            if ($complete && $completeImageCount < $patient->ecgImages()->count()) {
                throw ValidationException::withMessages(['complete' => 'Complete and save all twelve lead findings, overall interpretation, STEMI annotation and verified extraction for every ECG image before completing this examination.']);
            }
            $assessments = EcgInterpretation::where('examination_id', $exam->id)->pluck('assessment');
            $needsReview = $assessments->contains('Urgent Review');
            $outcome = ($needsReview || $assessments->contains('Abnormal')) ? 'Abnormal'
                : ($assessments->every(fn ($value) => $value === 'Normal') ? 'Normal' : 'Unable to Determine');
            $exam->update([
                'status' => $complete ? 'completed' : 'in_progress',
                'completed_at' => $complete ? now() : null,
                'overall_outcome' => $outcome, 'doctor_notes' => $data['notes'] ?? null,
            ]);
            $patient->update(['case_status' => $complete ? 'completed' : ($needsReview ? 'needs_review' : 'in_progress')]);
            return $finding->fresh();
        });
        $url = route('doctor.patients.show', ['patient' => $patient, 'image' => $data['ecg_image_id']]);
        $message = $request->boolean('complete') ? 'ECG findings saved. Examination completed.' : 'ECG findings saved.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'interpretation' => $interpretation,
                'completed' => $request->boolean('complete'),
                'redirect' => $url,
            ]);
        }
        return redirect()->to($url)->with('success', $message);
    }
    public function cases(Request $request): View
    {
        $examinations = Examination::query()->with(['patient' => fn ($query) => $query->withCount('ecgImages'), 'doctor'])
            ->when($request->user()->isDoctor(), fn ($query) => $query->where('doctor_id', $request->user()->id))
            ->latest('updated_at')->paginate(20);
        return view('stemi.cases', compact('examinations'));
    }

    public function reports(Request $request): View
    {
        $interpretations = EcgInterpretation::query()->with(['image.patient','doctor'])
            ->when($request->user()->isDoctor(), fn ($q) => $q->where('doctor_id', $request->user()->id))
            ->latest()->paginate(20);
        return view('stemi.reports', compact('interpretations'));
    }

    public function profile(Request $request): View { return view('stemi.profile'); }
}
