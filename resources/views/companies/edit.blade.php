@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Company</h1>

    <form action="{{ route('companies.update', $company) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $company->name) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="{{ old('slug', $company->slug) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Logo</label>
            <input type="text" name="logo" class="form-control" value="{{ old('logo', $company->logo) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" @selected($company->status === 'active')>Active</option>
                <option value="suspended" @selected($company->status === 'suspended')>Suspended</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
