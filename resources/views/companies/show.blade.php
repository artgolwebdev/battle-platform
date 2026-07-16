@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $company->name }}</h1>
    <p class="text-muted">{{ $company->slug }} · {{ ucfirst($company->status) }}</p>

    <div class="card">
        <div class="card-body">
            <p><strong>Owner admin:</strong> {{ $company->ownerAdmin?->name ?? 'None' }}</p>
            <p><strong>Users:</strong> {{ $company->users()->count() }}</p>
        </div>
    </div>
</div>
@endsection
