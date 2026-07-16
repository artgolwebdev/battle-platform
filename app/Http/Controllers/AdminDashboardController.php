<?php

namespace App\Http\Controllers;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        abort_unless($user && $user->hasRole('admin') && $user->company_id, 403);

        $company = $user->company()->with('users')->firstOrFail();

        return view('admin.dashboard', compact('company'));
    }
}
