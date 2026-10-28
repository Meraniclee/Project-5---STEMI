<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Consultant Dashboard.
     */
    public function index(): View
    {
        /*
        |--------------------------------------------------------------------------
        | Main Case Statistics
        |--------------------------------------------------------------------------
        */

        $totalCases =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
                ->count();


        $pendingCases =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
                ->where(
                    'case_status',
                    'pending'
                )
                ->count();


        $inProgressCases =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
                ->where(
                    'case_status',
                    'in_progress'
                )
                ->count();


        $completedCases =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
                ->where(
                    'case_status',
                    'completed'
                )
                ->count();


        $needsReviewCases =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
                ->where(
                    'case_status',
                    'needs_review'
                )
                ->count();


        $stats = [
            'total_cases' =>
                $totalCases,

            'pending_cases' =>
                $pendingCases,

            'in_progress_cases' =>
                $inProgressCases,

            'completed_cases' =>
                $completedCases,

            'needs_review_cases' =>
                $needsReviewCases,
        ];


        /*
        |--------------------------------------------------------------------------
        | Recent Cases
        |--------------------------------------------------------------------------
        */

        $recentCases =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
                ->withCount(
                    'heartRecordings'
                )
                ->with([
                    'examinations' =>
                        function ($query) {
                            $query
                                ->with(
                                    'doctor'
                                )
                                ->latest(
                                    'updated_at'
                                );
                        },
                ])
                ->latest(
                    'updated_at'
                )
                ->limit(8)
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Recent Completed Examinations
        |--------------------------------------------------------------------------
        */

        $recentCompleted =
            Examination::query()
                ->with([
                    'patient',
                    'doctor',
                ])
                ->where(
                    'status',
                    'completed'
                )
                ->latest(
                    'completed_at'
                )
                ->limit(6)
                ->get();


        return view(
            'consultant.dashboard',
            compact(
                'stats',
                'recentCases',
                'recentCompleted'
            )
        );
    }


    /**
     * Case Status page.
     */
    public function caseStatus(
        Request $request
    ): View {
        $search =
            trim(
                (string)
                $request->query(
                    'q',
                    ''
                )
            );


        $status =
            (string)
            $request->query(
                'status',
                'all'
            );


        $ageCategory =
            (string)
            $request->query(
                'age_category',
                'all'
            );


        $query =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
                ->withCount(
                    'heartRecordings'
                )
                ->with([
                    'examinations' =>
                        function ($query) {
                            $query
                                ->with(
                                    'doctor'
                                )
                                ->latest(
                                    'updated_at'
                                );
                        },
                ]);


        /*
        |--------------------------------------------------------------------------
        | Patient ID Search
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {
            $query->where(
                'subject_id',
                'like',
                "%{$search}%"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

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
            $query->where(
                'case_status',
                $status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Age Category Filter
        |--------------------------------------------------------------------------
        */

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
            $query->where(
                'age_category',
                $ageCategory
            );
        }


        $patients =
            $query
                ->latest(
                    'updated_at'
                )
                ->paginate(12)
                ->withQueryString();


        return view(
            'consultant.case-status',
            compact(
                'patients',
                'search',
                'status',
                'ageCategory'
            )
        );
    }


    /**
     * Patient Records page.
     */
    public function patients(
        Request $request
    ): View {
        $search =
            trim(
                (string)
                $request->query(
                    'q',
                    ''
                )
            );


        $query =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
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


        $patients =
            $query
                ->latest(
                    'updated_at'
                )
                ->paginate(12)
                ->withQueryString();


        return view(
            'consultant.patients',
            compact(
                'patients',
                'search'
            )
        );
    }


    /**
     * Read-only patient details.
     */
    public function patientShow(
        Patient $patient
    ): View {
        $patient->load([
            'heartRecordings',
            'examinations' =>
                function ($query) {
                    $query
                        ->with([
                            'doctor',
                            'findings',
                        ])
                        ->latest(
                            'updated_at'
                        );
                },
        ]);


        return view(
            'consultant.patient-show',
            compact(
                'patient'
            )
        );
    }


    /**
     * Consultant profile.
     */
    public function profile(
        Request $request
    ): View {
        return view(
            'consultant.profile',
            [
                'consultant' =>
                    $request->user(),
            ]
        );
    }
}