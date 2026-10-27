<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\Examination;

use App\Models\Patient;

use App\Models\User;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

use Illuminate\View\View;



class UserManagementController extends Controller

{

    /*

    |--------------------------------------------------------------------------

    | Doctor

    |--------------------------------------------------------------------------

    */



    public function doctors(Request $request): View

    {

        return $this->indexByRole(

            request: $request,

            role: 'doctor'

        );

    }



    public function doctorShow(
        Request $request,
        User $user
    ): View {
        $this->ensureRole(
            $user,
            'doctor'
        );

        $search = trim(
            (string) $request->query(
                'q',
                ''
            )
        );

        $status = (string) $request->query(
            'status',
            'all'
        );

        $patientsQuery = Patient::query()
            ->whereHas(
                'examinations',
                function ($query) use ($user) {
                    $query->where(
                        'doctor_id',
                        $user->id
                    );
                }
            )
            ->withCount('ecgImages')
            ->withCount([
                'examinations as doctor_examinations_count' =>
                    function ($query) use ($user) {
                        $query->where(
                            'doctor_id',
                            $user->id
                        );
                    },
            ])
            ->with([
                'examinations' =>
                    function ($query) use ($user) {
                        $query
                            ->where(
                                'doctor_id',
                                $user->id
                            )
                            ->with('doctor')
                            ->latest('started_at')
                            ->latest('id');
                    },
            ])
            ->addSelect([
                'doctor_last_examined_at' =>
                    Examination::query()
                        ->select('started_at')
                        ->whereColumn(
                            'patient_id',
                            'patients.id'
                        )
                        ->where(
                            'doctor_id',
                            $user->id
                        )
                        ->latest('started_at')
                        ->latest('id')
                        ->limit(1),
            ]);

        if ($search !== '') {
            $patientsQuery->where(
                function ($query) use ($search) {
                    $query
                        ->where(
                            'subject_id',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'age_category',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'sex',
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
                    'pending',
                    'in_progress',
                    'completed',
                    'needs_review',
                ],
                true
            )
        ) {
            $patientsQuery->where(
                'case_status',
                $status
            );
        }

        $patients = $patientsQuery
            ->orderByDesc('doctor_last_examined_at')
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        $totalPatients = Examination::query()
            ->where(
                'doctor_id',
                $user->id
            )
            ->distinct()
            ->count('patient_id');

        $totalExaminations = Examination::query()
            ->where(
                'doctor_id',
                $user->id
            )
            ->count();

        $activeExaminations = Examination::query()
            ->where(
                'doctor_id',
                $user->id
            )
            ->whereIn(
                'status',
                [
                    'draft',
                    'in_progress',
                ]
            )
            ->count();

        $completedExaminations = Examination::query()
            ->where(
                'doctor_id',
                $user->id
            )
            ->where(
                'status',
                'completed'
            )
            ->count();

        return view(
            'admin.users.doctor-show',
            [
                'doctor' => $user,
                'patients' => $patients,
                'search' => $search,
                'status' => $status,
                'totalPatients' => $totalPatients,
                'totalExaminations' => $totalExaminations,
                'activeExaminations' => $activeExaminations,
                'completedExaminations' => $completedExaminations,
            ]
        );
    }



    public function doctorCreate(): View

    {

        return $this->createByRole('doctor');

    }



    public function doctorStore(Request $request): RedirectResponse

    {

        return $this->storeByRole(

            request: $request,

            role: 'doctor'

        );

    }



    public function doctorEdit(User $user): View

    {

        return $this->editByRole(

            user: $user,

            role: 'doctor'

        );

    }



    public function doctorUpdate(

        Request $request,

        User $user

    ): RedirectResponse {

        return $this->updateByRole(

            request: $request,

            user: $user,

            role: 'doctor'

        );

    }



    public function doctorToggleStatus(User $user): RedirectResponse

    {

        return $this->toggleStatusByRole(

            user: $user,

            role: 'doctor'

        );

    }



    public function doctorDestroy(User $user): RedirectResponse

    {

        $this->ensureRole($user, 'doctor');



        /*

        |--------------------------------------------------------------------------

        | Preserve clinical history

        |--------------------------------------------------------------------------

        |

        | A Doctor who already has examination records should not be physically

        | deleted because those records need to remain linked to the Doctor.

        |

        */



        if ($user->examinations()->exists()) {

            return redirect()

                ->route('admin.doctors')

                ->with(

                    'error',

                    'This Doctor cannot be removed because examination records are linked to the account. Deactivate the account instead.'

                );

        }



        $name = $user->name;



        $user->delete();



        return redirect()

            ->route('admin.doctors')

            ->with(

                'success',

                "{$name} was removed successfully."

            );

    }





    /*

    |--------------------------------------------------------------------------

    | Consultant

    |--------------------------------------------------------------------------

    */



    public function consultants(Request $request): View

    {

        return $this->indexByRole(

            request: $request,

            role: 'consultant'

        );

    }



    public function consultantCreate(): View

    {

        return $this->createByRole('consultant');

    }



    public function consultantStore(Request $request): RedirectResponse

    {

        return $this->storeByRole(

            request: $request,

            role: 'consultant'

        );

    }



    public function consultantEdit(User $user): View

    {

        return $this->editByRole(

            user: $user,

            role: 'consultant'

        );

    }



    public function consultantUpdate(

        Request $request,

        User $user

    ): RedirectResponse {

        return $this->updateByRole(

            request: $request,

            user: $user,

            role: 'consultant'

        );

    }



    public function consultantToggleStatus(User $user): RedirectResponse

    {

        return $this->toggleStatusByRole(

            user: $user,

            role: 'consultant'

        );

    }



    public function consultantDestroy(User $user): RedirectResponse

    {

        $this->ensureRole($user, 'consultant');



        $name = $user->name;



        $user->delete();



        return redirect()

            ->route('admin.consultants')

            ->with(

                'success',

                "{$name} was removed successfully."

            );

    }





    /*

    |--------------------------------------------------------------------------

    | Shared Index

    |--------------------------------------------------------------------------

    */



    private function indexByRole(

        Request $request,

        string $role

    ): View {

        $search = trim(

            (string) $request->query('q', '')

        );



        $status = (string) $request->query(

            'status',

            'all'

        );



        $query = User::query()

            ->where('role', $role);



        if ($search !== '') {

            $query->where(

                function ($builder) use ($search) {

                    $builder

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

                            'phone',

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



        if (

            in_array(

                $status,

                ['active', 'inactive'],

                true

            )

        ) {

            $query->where(

                'status',

                $status

            );

        }



        $users = $query

            ->orderBy('name')

            ->paginate(10)

            ->withQueryString();





        /*

        |--------------------------------------------------------------------------

        | Doctor Patient Counts

        |--------------------------------------------------------------------------

        |

        | Count unique patients managed by each Doctor shown on the current

        | page. A patient is counted only once for a Doctor, even if that

        | Doctor has performed multiple examinations on the same patient.

        |

        | Consultants do not require this count.

        |

        */



        if (

            $role === 'doctor'

            &&

            $users->count() > 0

        ) {

            $doctorIds = $users

                ->getCollection()

                ->pluck('id')

                ->all();



            $patientsManagedCounts =

                Examination::query()

                    ->whereIn(

                        'doctor_id',

                        $doctorIds

                    )

                    ->select('doctor_id')

                    ->selectRaw(

                        'COUNT(DISTINCT patient_id) as patients_managed_count'

                    )

                    ->groupBy('doctor_id')

                    ->pluck(

                        'patients_managed_count',

                        'doctor_id'

                    );



            $users

                ->getCollection()

                ->each(

                    function (User $managedUser) use ($patientsManagedCounts): void {

                        $managedUser->setAttribute(

                            'patients_managed_count',

                            (int) (

                                $patientsManagedCounts[

                                    $managedUser->id

                                ]

                                ?? 0

                            )

                        );

                    }

                );

        }





        /*

        |--------------------------------------------------------------------------

        | User Statistics

        |--------------------------------------------------------------------------

        */



        $totalUsers = User::where(

            'role',

            $role

        )->count();



        $activeUsers = User::where(

            'role',

            $role

        )

            ->where(

                'status',

                'active'

            )

            ->count();



        $inactiveUsers = User::where(

            'role',

            $role

        )

            ->where(

                'status',

                'inactive'

            )

            ->count();



        return view(

            'admin.users.index',

            [

                'users' => $users,

                'role' => $role,

                'search' => $search,

                'status' => $status,

                'totalUsers' => $totalUsers,

                'activeUsers' => $activeUsers,

                'inactiveUsers' => $inactiveUsers,

            ]

        );

    }





    /*

    |--------------------------------------------------------------------------

    | Shared Create

    |--------------------------------------------------------------------------

    */



    private function createByRole(

        string $role

    ): View {

        return view(

            'admin.users.form',

            [

                'role' => $role,

                'user' => null,

                'mode' => 'create',

            ]

        );

    }





    /*

    |--------------------------------------------------------------------------

    | Shared Store

    |--------------------------------------------------------------------------

    */



    private function storeByRole(

        Request $request,

        string $role

    ): RedirectResponse {

        $validated = $this->validateUser(

            request: $request,

            role: $role

        );



        $validated['role'] = $role;



        User::create($validated);



        return redirect()

            ->route(

                $this->indexRoute($role)

            )

            ->with(

                'success',

                ucfirst($role) .

                ' account created successfully.'

            );

    }





    /*

    |--------------------------------------------------------------------------

    | Shared Edit

    |--------------------------------------------------------------------------

    */



    private function editByRole(

        User $user,

        string $role

    ): View {

        $this->ensureRole(

            $user,

            $role

        );



        return view(

            'admin.users.form',

            [

                'role' => $role,

                'user' => $user,

                'mode' => 'edit',

            ]

        );

    }





    /*

    |--------------------------------------------------------------------------

    | Shared Update

    |--------------------------------------------------------------------------

    */



    private function updateByRole(

        Request $request,

        User $user,

        string $role

    ): RedirectResponse {

        $this->ensureRole(

            $user,

            $role

        );



        $validated = $this->validateUser(

            request: $request,

            role: $role,

            user: $user

        );



        /*

        |--------------------------------------------------------------------------

        | Keep current password when blank

        |--------------------------------------------------------------------------

        */



        if (

            !isset($validated['password']) ||

            $validated['password'] === null ||

            $validated['password'] === ''

        ) {

            unset(

                $validated['password']

            );

        }



        $validated['role'] = $role;



        $user->update($validated);



        return redirect()

            ->route(

                $this->indexRoute($role)

            )

            ->with(

                'success',

                ucfirst($role) .

                ' account updated successfully.'

            );

    }





    /*

    |--------------------------------------------------------------------------

    | Shared Status Toggle

    |--------------------------------------------------------------------------

    */



    private function toggleStatusByRole(

        User $user,

        string $role

    ): RedirectResponse {

        $this->ensureRole(

            $user,

            $role

        );



        $newStatus =

            $user->status === 'active'

                ? 'inactive'

                : 'active';



        $user->update([

            'status' => $newStatus,

        ]);



        return redirect()

            ->route(

                $this->indexRoute($role)

            )

            ->with(

                'success',

                $user->name .

                ' is now ' .

                $newStatus .

                '.'

            );

    }





    /*

    |--------------------------------------------------------------------------

    | Validation

    |--------------------------------------------------------------------------

    */



    private function validateUser(

        Request $request,

        string $role,

        ?User $user = null

    ): array {

        $emailRule = Rule::unique(

            'users',

            'email'

        );



        if ($user) {

            $emailRule->ignore(

                $user->id

            );

        }



        $passwordRules =

            $user

                ? [

                    'nullable',

                    'string',

                    'min:8',

                    'confirmed',

                ]

                : [

                    'required',

                    'string',

                    'min:8',

                    'confirmed',

                ];



        $specializationRules =

            $role === 'doctor'

                ? [

                    'required',

                    'string',

                    'max:255',

                ]

                : [

                    'nullable',

                    'string',

                    'max:255',

                ];



        return $request->validate(

            [

                'name' => [

                    'required',

                    'string',

                    'max:255',

                ],



                'email' => [

                    'required',

                    'email',

                    'max:255',

                    $emailRule,

                ],



                'phone' => [

                    'nullable',

                    'string',

                    'max:30',

                ],



                'specialization' =>

                    $specializationRules,



                'status' => [

                    'required',

                    Rule::in([

                        'active',

                        'inactive',

                    ]),

                ],



                'password' =>

                    $passwordRules,

            ],

            [

                'name.required' =>

                    'Please enter the user name.',



                'email.required' =>

                    'Please enter an email address.',



                'email.email' =>

                    'Please enter a valid email address.',



                'email.unique' =>

                    'This email address is already registered.',



                'specialization.required' =>

                    'Please enter the Doctor specialization.',



                'password.required' =>

                    'Please create a password.',



                'password.min' =>

                    'The password must contain at least 8 characters.',



                'password.confirmed' =>

                    'The password confirmation does not match.',

            ]

        );

    }





    /*

    |--------------------------------------------------------------------------

    | Role Safety

    |--------------------------------------------------------------------------

    */



    private function ensureRole(

        User $user,

        string $expectedRole

    ): void {

        abort_if(

            $user->role !== $expectedRole,

            404

        );

    }





    /*

    |--------------------------------------------------------------------------

    | Route Helper

    |--------------------------------------------------------------------------

    */



    private function indexRoute(

        string $role

    ): string {

        return $role === 'doctor'

            ? 'admin.doctors'

            : 'admin.consultants';

    }

}