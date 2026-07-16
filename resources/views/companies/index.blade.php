@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Companies</h1>
        @can('create', App\Models\Company::class)
            <a href="{{ route('companies.create') }}" class="btn btn-primary">Create Company</a>
        @endcan
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($companies->isEmpty())
                <p class="mb-0">No companies yet.</p>
            @else
                <ul class="list-group">
                    @foreach($companies as $company)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $company->name }}</strong>
                                <div class="text-muted">{{ $company->slug }} · {{ ucfirst($company->status) }}</div>
                            </div>
                            <div>
                                <a href="{{ route('companies.show', $company) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @can('update', $company)
                                    <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
