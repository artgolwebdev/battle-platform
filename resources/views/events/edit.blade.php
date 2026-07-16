@extends('layouts.app')

@section('content')
<div class="container">
    <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-secondary mb-3">&#8592; Back to Events</a>
    <h1>Edit Event</h1>

    <form action="{{ route('events.update', $event) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $event->title) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ old('description', $event->description) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" value="{{ old('location', $event->location) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Start Date</label>
            <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date', optional($event->start_date)->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">End Date</label>
            <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date', optional($event->end_date)->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Programme JSON</label>
            <textarea name="programme" class="form-control" rows="4">{{ old('programme', json_encode($event->programme ?? [], JSON_PRETTY_PRINT)) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Banner</label>
            <input type="file" name="banner" class="form-control">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="registration_open" value="1" class="form-check-input" @checked($event->registration_open)>
            <label class="form-check-label">Registration Open</label>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
