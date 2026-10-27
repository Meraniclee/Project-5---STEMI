<?php

namespace App\Http\Controllers\Stemi;

use App\Http\Controllers\Controller;
use App\Models\EcgInterpretation;
use App\Models\Patient;
use App\Support\ExaminationReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExaminationReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $scope = fn ($query) => $query->when($user->isDoctor(), fn ($query) => $query->where('doctor_id', $user->id));
        $lastSaved = EcgInterpretation::query()->selectRaw('MAX(ecg_interpretations.updated_at)')
            ->join('ecg_images', 'ecg_images.id', '=', 'ecg_interpretations.ecg_image_id')
            ->whereColumn('ecg_images.patient_id', 'patients.id')
            ->when($user->isDoctor(), fn ($query) => $query->where('ecg_interpretations.doctor_id', $user->id));
        $patients = Patient::query()->whereHas('ecgImages.interpretations', $scope)
            ->when($search !== '', fn ($query) => $query->where('subject_id', 'like', '%'.$search.'%'))
            ->with([
                'ecgImages:id,patient_id',
                'ecgImages.interpretations' => fn ($query) => $scope($query)->select('id', 'ecg_image_id', 'examination_id', 'doctor_id', 'updated_at', 'annotation_complete')->with('doctor:id,name'),
            ])
            ->addSelect(['latest_report_at' => $lastSaved])->orderByDesc('latest_report_at')->orderBy('subject_id')
            ->paginate(12)->withQueryString();

        return view('stemi.reports', compact('patients', 'search'));
    }

    public function show(Request $request, Patient $patient): View
    {
        $findings = EcgInterpretation::query()->whereHas('image', fn ($query) => $query->where('patient_id', $patient->id))
            ->when($request->user()->isDoctor(), fn ($query) => $query->where('doctor_id', $request->user()->id))
            ->with(['image', 'doctor', 'examination'])->orderByDesc('examination_id')->orderBy('ecg_image_id')->get();
        abort_if($findings->isEmpty(), 404);

        $report = ExaminationReport::forPatient($patient, $findings);
        return view('stemi.examination-report', compact('patient', 'report'));
    }
}
