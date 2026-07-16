<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        if ($user && $user->hasRole('superadmin')) {
            return redirect()->route('superadmin.dashboard');
        }

        if ($user && $user->hasRole('admin')) {
            return redirect()->route('dashboard.admin');
        }

        return view('home');
    }
}
