<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserRegistrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $registrations = auth()->user()->registrations()->with('event')->latest()->paginate(15);
        return view('registrations.index', compact('registrations'));
    }
}
