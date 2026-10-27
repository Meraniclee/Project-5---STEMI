<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirect authenticated users to their role dashboard.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'consultant' => redirect()->route('consultant.dashboard'),
            'doctor' => redirect()->route('doctor.dashboard'),
            default => abort(403, 'Invalid STEMI user role.'),
        };
    }
}