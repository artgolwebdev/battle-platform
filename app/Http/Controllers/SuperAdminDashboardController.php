<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;

class SuperAdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::withCount('users')->latest()->get();
        $pendingCompanies = Company::where('status', 'pending')->with('ownerAdmin')->latest()->get();
        $users = User::with('company')->latest()->paginate(15);

        return view('superadmin.dashboard', compact('companies', 'pendingCompanies', 'users'));
    }
}
