<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Notifications\CompanyApprovedNotification;
use App\Notifications\CompanyRejectedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::latest()->paginate(15);

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        $this->authorize('create', Company::class);

        return view('companies.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Company::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:companies,slug'],
            'logo' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,suspended'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($request->input('name'));
        $data['status'] = $data['status'] ?? 'active';

        Company::create($data);

        return redirect()->route('companies.index')->with('status', 'Company created.');
    }

    public function show(Company $company)
    {
        $this->authorize('view', $company);

        return view('companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        $this->authorize('update', $company);

        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorize('update', $company);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:companies,slug,' . $company->id],
            'logo' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,suspended'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($request->input('name'));

        $company->update($data);

        return redirect()->route('companies.index')->with('status', 'Company updated.');
    }

    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);

        $company->delete();

        return redirect()->route('companies.index')->with('status', 'Company deleted.');
    }

    public function suspend(Company $company)
    {
        $this->authorize('suspend', $company);

        $company->update(['status' => 'suspended']);

        return redirect()->back()->with('status', 'Company suspended.');
    }

    public function activate(Company $company)
    {
        $this->authorize('activate', $company);

        $company->update(['status' => 'active']);

        return redirect()->back()->with('status', 'Company activated.');
    }

    public function approve(Company $company)
    {
        $this->authorize('update', $company); // Superadmin only

        $company->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        if ($company->ownerAdmin) {
            $company->ownerAdmin->notify(new CompanyApprovedNotification($company));
        }

        return redirect()->back()->with('status', 'Company approved.');
    }

    public function reject(Company $company)
    {
        $this->authorize('update', $company); // Superadmin only

        $company->update(['status' => 'rejected']);

        if ($company->ownerAdmin) {
            $company->ownerAdmin->notify(new CompanyRejectedNotification($company));
        }

        return redirect()->back()->with('status', 'Company rejected.');
    }
}
