<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display Doctor Dashboard.
     */
    public function index(Request $request): View
    {
        $doctor = $request->user();

        $totalPatients = Patient::query()
            ->whereHas('heartRecordings')
            ->count();

        $myPatients = Patient::query()
            ->whereHas('examinations', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->count();

        $availablePatients = Patient::query()
            ->whereHas('heartRecordings')
            ->whereDoesntHave('examinations')
            ->count();

        $myActiveCases = Examination::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', [
                'draft',
                'in_progress',
            ])
            ->count();

        $myCompletedCases = Examination::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->count();

        $needsReview = Patient::query()
            ->whereHas('heartRecordings')
            ->where('case_status', 'needs_review')
            ->count();

        $stats = [
            'total_patients' => $totalPatients,
            'my_patients' => $myPatients,
            'available_patients' => $availablePatients,
            'my_active_cases' => $myActiveCases,
            'my_completed_cases' => $myCompletedCases,
            'needs_review' => $needsReview,
        ];

        $recentAvailablePatients = Patient::query()
            ->whereHas('heartRecordings')
            ->whereDoesntHave('examinations')
            ->withCount('heartRecordings')
            ->with('examinations')
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $recentMyPatients = Patient::query()
            ->whereHas('examinations', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->withCount('heartRecordings')
            ->with([
                'examinations' => function ($query) use ($doctor) {
                    $query
                        ->where('doctor_id', $doctor->id)
                        ->latest('updated_at');
                },
            ])
            ->withMax(
                [
                    'examinations as my_last_activity_at' =>
                        function ($query) use ($doctor) {
                            $query->where('doctor_id', $doctor->id);
                        },
                ],
                'updated_at'
            )
            ->orderByDesc('my_last_activity_at')
            ->limit(5)
            ->get();

        $myRecentCases = Examination::query()
            ->with('patient')
            ->where('doctor_id', $doctor->id)
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view(
            'doctor.dashboard',
            compact(
                'stats',
                'recentAvailablePatients',
                'recentMyPatients',
                'myRecentCases'
            )
        );
    }

    /**
     * Display patients that have never been examined by any Doctor.
     */
    public function patients(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $ageCategory = (string) $request->query('age_category', 'all');
        $sex = (string) $request->query('sex', 'all');
        $murmur = (string) $request->query('murmur', 'all');
        $outcome = (string) $request->query('outcome', 'all');

        $query = Patient::query()
            ->whereHas('heartRecordings')
            ->whereDoesntHave('examinations')
            ->withCount([
                'heartRecordings',
                'examinations',
            ]);

        if ($search !== '') {
            $query->where(
                'subject_id',
                'like',
                "%{$search}%"
            );
        }

        if (
            in_array(
                $status,
                [
                    'pending',
                    'in_progress',
                    'completed',
                    'needs_review',
                ],
                true
            )
        ) {
            $query->where('case_status', $status);
        }

        if (
            in_array(
                $ageCategory,
                [
                    'Neonate',
                    'Infant',
                    'Child',
                    'Adolescent',
                    'Young Adult',
                ],
                true
            )
        ) {
            $query->where('age_category', $ageCategory);
        }

        if (in_array($sex, ['Male', 'Female'], true)) {
            $query->where('sex', $sex);
        }

        if (
            in_array(
                $murmur,
                [
                    'Present',
                    'Absent',
                    'Unknown',
                ],
                true
            )
        ) {
            $query->where('murmur_status', $murmur);
        }

        if (in_array($outcome, ['Normal', 'Abnormal'], true)) {
            $query->where('clinical_outcome', $outcome);
        }

        $patients = $query
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $availableBase = Patient::query()
            ->whereHas('heartRecordings')
            ->whereDoesntHave('examinations');

        $summary = [
            'total' => (clone $availableBase)->count(),
            'pending' => (clone $availableBase)
                ->where('case_status', 'pending')
                ->count(),
            'needs_review' => (clone $availableBase)
                ->where('case_status', 'needs_review')
                ->count(),
            'murmur_present' => (clone $availableBase)
                ->where('murmur_status', 'Present')
                ->count(),
            'abnormal' => (clone $availableBase)
                ->where('clinical_outcome', 'Abnormal')
                ->count(),
        ];

        return view(
            'doctor.patients',
            compact(
                'patients',
                'search',
                'status',
                'ageCategory',
                'sex',
                'murmur',
                'outcome',
                'summary'
            )
        );
    }

    /**
     * Display unique patients who have been examined by the logged-in Doctor.
     */
    public function myPatients(Request $request): View
    {
        $doctor = $request->user();

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $ageCategory = (string) $request->query('age_category', 'all');
        $sex = (string) $request->query('sex', 'all');
        $murmur = (string) $request->query('murmur', 'all');
        $outcome = (string) $request->query('outcome', 'all');

        $query = Patient::query()
            ->whereHas('examinations', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->withCount('heartRecordings')
            ->withCount([
                'examinations as my_examinations_count' =>
                    function ($query) use ($doctor) {
                        $query->where('doctor_id', $doctor->id);
                    },
            ])
            ->with([
                'examinations' =>
                    function ($query) use ($doctor) {
                        $query
                            ->where('doctor_id', $doctor->id)
                            ->latest('updated_at');
                    },
            ]);

        if ($search !== '') {
            $query->where(
                'subject_id',
                'like',
                "%{$search}%"
            );
        }

        if (
            in_array(
                $status,
                [
                    'pending',
                    'in_progress',
                    'completed',
                    'needs_review',
                ],
                true
            )
        ) {
            $query->where('case_status', $status);
        }

        if (
            in_array(
                $ageCategory,
                [
                    'Neonate',
                    'Infant',
                    'Child',
                    'Adolescent',
                    'Young Adult',
                ],
                true
            )
        ) {
            $query->where('age_category', $ageCategory);
        }

        if (in_array($sex, ['Male', 'Female'], true)) {
            $query->where('sex', $sex);
        }

        if (
            in_array(
                $murmur,
                [
                    'Present',
                    'Absent',
                    'Unknown',
                ],
                true
            )
        ) {
            $query->where('murmur_status', $murmur);
        }

        if (in_array($outcome, ['Normal', 'Abnormal'], true)) {
            $query->where('clinical_outcome', $outcome);
        }

        $patients = $query
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $myPatientsBase = Patient::query()
            ->whereHas('examinations', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            });

        $summary = [
            'total' => (clone $myPatientsBase)->count(),
            'active' => (clone $myPatientsBase)
                ->whereHas('examinations', function ($query) use ($doctor) {
                    $query
                        ->where('doctor_id', $doctor->id)
                        ->whereIn('status', [
                            'draft',
                            'in_progress',
                        ]);
                })
                ->count(),
            'completed' => (clone $myPatientsBase)
                ->whereHas('examinations', function ($query) use ($doctor) {
                    $query
                        ->where('doctor_id', $doctor->id)
                        ->where('status', 'completed');
                })
                ->count(),
            'needs_review' => (clone $myPatientsBase)
                ->where('case_status', 'needs_review')
                ->count(),
            'examinations' => Examination::query()
                ->where('doctor_id', $doctor->id)
                ->count(),
        ];

        return view(
            'doctor.my-patients',
            compact(
                'patients',
                'search',
                'status',
                'ageCategory',
                'sex',
                'murmur',
                'outcome',
                'summary'
            )
        );
    }

    /**
     * Display cases belonging to the logged-in Doctor.
     */
    public function myCases(Request $request): View
    {
        $doctor = $request->user();

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        $query = Examination::query()
            ->with('patient')
            ->where('doctor_id', $doctor->id);

        if ($search !== '') {
            $query->whereHas(
                'patient',
                function ($query) use ($search) {
                    $query->where(
                        'subject_id',
                        'like',
                        "%{$search}%"
                    );
                }
            );
        }

        if (
            in_array(
                $status,
                [
                    'draft',
                    'in_progress',
                    'completed',
                ],
                true
            )
        ) {
            $query->where('status', $status);
        }

        $examinations = $query
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view(
            'doctor.my-cases',
            compact(
                'examinations',
                'search',
                'status'
            )
        );
    }

    /**
     * Display Doctor profile.
     */
    public function profile(Request $request): View
    {
        return view(
            'doctor.profile',
            [
                'doctor' => $request->user(),
            ]
        );
    }
}
