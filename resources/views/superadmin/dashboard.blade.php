@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Superadmin Dashboard</h1>

    <div class="row g-4 mt-4">
        <div class="col-md-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">Pending Company Approvals</div>
                <div class="card-body">
                    @if($pendingCompanies->isEmpty())
                        <p class="text-muted mb-0">No pending company requests.</p>
                    @else
                        <ul class="list-group">
                            @foreach($pendingCompanies as $company)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $company->name }}</strong>
                                        <br>
                                        <small class="text-muted">Requested by: {{ $company->ownerAdmin?->name }} ({{ $company->ownerAdmin?->email }})</small>
                                    </div>
                                    <div>
                                        <form action="{{ route('companies.approve', $company) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form action="{{ route('companies.reject', $company) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this request?')">Reject</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">All Companies</div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($companies as $company)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    {{ $company->name }}
                                    @if($company->status === 'pending')
                                        <span class="badge bg-warning text-dark ms-2">Pending</span>
                                    @elseif($company->status === 'rejected')
                                        <span class="badge bg-danger ms-2">Rejected</span>
                                    @endif
                                </span>
                                <span class="badge bg-secondary">{{ $company->users_count }} users</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Users</div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($users as $user)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $user->name }} ({{ $user->email }})</span>
                                <span class="badge bg-info text-dark">{{ $user->company?->name ?? 'No company' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
