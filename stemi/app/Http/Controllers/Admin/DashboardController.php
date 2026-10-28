<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $search =
            trim(
                (string) $request->query(
                    'q',
                    ''
                )
            );


        $requestedRole =
            (string) $request->query(
                'user_role',
                'doctor'
            );


        $selectedUserRole =
            in_array(
                $requestedRole,
                [
                    'doctor',
                    'consultant',
                ],
                true
            )
                ? $requestedRole
                : 'doctor';


        /*
        |--------------------------------------------------------------------------
        | Dashboard Statistics
        |--------------------------------------------------------------------------
        |
        | Inspected Patients:
        | Patients with at least one Doctor examination record.
        |
        | Uninspected Patients:
        | Patients that do not have any examination records yet.
        |
        | A patient is counted only once even when the patient has multiple
        | examinations or has been examined by multiple Doctors.
        |
        */

        $stats = [
            'total_patients' =>
                Patient::count(),

            'inspected_patients' =>
                Patient::query()
                    ->whereHas(
                        'examinations'
                    )
                    ->count(),

            'uninspected_patients' =>
                Patient::query()
                    ->whereDoesntHave(
                        'examinations'
                    )
                    ->count(),

            'doctors' =>
                User::where(
                    'role',
                    'doctor'
                )
                    ->where(
                        'status',
                        'active'
                    )
                    ->count(),

            'consultants' =>
                User::where(
                    'role',
                    'consultant'
                )
                    ->where(
                        'status',
                        'active'
                    )
                    ->count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Manage Users Preview
        |--------------------------------------------------------------------------
        */

        $userQuery =
            User::query()
                ->where(
                    'role',
                    $selectedUserRole
                );


        if (
            $search !==
            ''
        ) {
            $userQuery
                ->where(
                    function ($query) use ($search) {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'email',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'specialization',
                                'like',
                                "%{$search}%"
                            );
                    }
                );
        }


        $manageUsers =
            $userQuery
                ->orderBy(
                    'name'
                )
                ->limit(
                    5
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Recent Uploads / Patient Records
        |--------------------------------------------------------------------------
        |
        | Only patients that currently have at least one uploaded
        | heart-sound recording are shown here.
        |
        | The patient itself can remain in the patients table after
        | its recordings are removed, but it will no longer appear
        | in the Recent Uploads section.
        |
        */

        $patientQuery =
            Patient::query()
                ->whereHas(
                    'heartRecordings'
                )
                ->with([
                    'heartRecordings.uploader',
                    'latestExamination.doctor',
                ]);


        if (
            $search !==
            ''
        ) {
            $patientQuery
                ->where(
                    'subject_id',
                    'like',
                    "%{$search}%"
                );
        }


        $recentPatients =
            $patientQuery
                ->latest(
                    'updated_at'
                )
                ->limit(
                    5
                )
                ->get();


        return view(
            'admin.dashboard',
            compact(
                'stats',
                'manageUsers',
                'recentPatients',
                'selectedUserRole',
                'search'
            )
        );
    }
}