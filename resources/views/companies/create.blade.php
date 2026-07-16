@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Company</h1>

    <form action="{{ route('companies.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Logo</label>
            <input type="text" name="logo" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
