@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Event</h1>

    <form action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Start Date</label>
            <input type="datetime-local" name="start_date" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">End Date</label>
            <input type="datetime-local" name="end_date" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Programme JSON</label>
            <textarea name="programme" class="form-control" rows="4">[]</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Banner</label>
            <input type="file" name="banner" class="form-control">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="registration_open" value="1" class="form-check-input" checked>
            <label class="form-check-label">Registration Open</label>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
