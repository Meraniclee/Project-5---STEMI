<?php

namespace App\Http\Controllers\Stemi;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultantDoctorController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $doctors = User::where('role', 'doctor')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('specialization', 'like', '%'.$search.'%');
            }))
            ->withCount(['examinations', 'examinations as completed_examinations_count' => fn ($query) => $query->where('status', 'completed')])
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('stemi.consultant-doctors', compact('doctors', 'search'));
    }

    public function show(Request $request, User $user): View
    {
        abort_unless($user->isDoctor(), 404);
        $doctor = $user;
        $search = trim((string) $request->query('q', ''));
        $patients = Patient::whereHas('examinations', fn ($query) => $query->where('doctor_id', $doctor->id))
            ->when($search !== '', fn ($query) => $query->where('subject_id', 'like', '%'.$search.'%'))
            ->withCount('ecgImages')
            ->with(['examinations' => fn ($query) => $query->where('doctor_id', $doctor->id)->latest()])
            ->latest('updated_at')->paginate(12)->withQueryString();
        $doctor->loadCount(['examinations', 'examinations as active_examinations_count' => fn ($query) => $query->whereIn('status', ['draft', 'in_progress'])]);

        return view('stemi.consultant-doctor-show', compact('doctor', 'patients', 'search'));
    }
}
