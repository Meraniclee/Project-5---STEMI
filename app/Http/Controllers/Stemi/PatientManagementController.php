<?php

namespace App\Http\Controllers\Stemi;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientManagementController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('stemi.patient-form', ['patient' => new Patient]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $patient = Patient::create($this->validated($request) + ['case_status' => 'pending']);

        return redirect()->route('admin.patients.show', $patient)
            ->with('success', 'Patient added. ECG images can now be uploaded to this record.');
    }

    public function edit(Request $request, Patient $patient): View
    {
        $this->authorizeEdit($request, $patient);

        return view('stemi.patient-form', compact('patient'));
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorizeEdit($request, $patient);
        $data = $this->validated($request, $patient);
        DB::transaction(function () use ($request, $patient, $data): void {
            $lockedPatient = Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            $this->authorizeEdit($request, $lockedPatient, true);
            $lockedPatient->update($data);
        });

        return redirect()->route($request->user()->role.'.patients.show', $patient)
            ->with('success', 'Patient information updated.');
    }

    private function authorizeEdit(Request $request, Patient $patient, bool $lock = false): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        abort_unless($request->user()->isDoctor(), 403);
        $query = Examination::where('patient_id', $patient->id)
            ->where('doctor_id', $request->user()->id)
            ->whereIn('status', ['draft', 'in_progress']);
        if ($lock) {
            $query->lockForUpdate();
        }
        abort_unless($query->first(), 403, 'Start an examination for this patient before editing their information.');
    }

    private function validated(Request $request, ?Patient $patient = null): array
    {
        return $request->validate([
            'subject_id' => ['required', 'string', 'max:100', Rule::unique('patients', 'subject_id')->ignore($patient?->id)],
            'age_category' => ['nullable', Rule::in(['Neonate', 'Infant', 'Child', 'Adolescent', 'Young Adult', 'Adult', 'Older Adult'])],
            'sex' => ['nullable', Rule::in(['Male', 'Female'])],
        ]);
    }
}
