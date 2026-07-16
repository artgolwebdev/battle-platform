@extends('layouts.app')

@section('content')
<div class="container">
    <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-outline-secondary mb-3">&#8592; Back to Event</a>
    <h1>Registrations for {{ $event->title }}</h1>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                @if($event->categories->isNotEmpty())
                    <th>Category</th>
                @endif
                <th>Status</th>
                <th>Responses</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($event->registrations as $registration)
                <tr>
                    <td>{{ $registration->name }}</td>
                    <td>{{ $registration->email }}</td>
                    @if($event->categories->isNotEmpty())
                        <td>{{ $registration->category ? $registration->category->name : 'N/A' }}</td>
                    @endif
                    <td>{{ $registration->status ?? 'pending' }}</td>
                    <td>
                        @if($registration->responses)
                            <ul class="mb-0">
                                @foreach($registration->responses as $key => $value)
                                    <li>{{ $key }}: {{ is_array($value) ? implode(', ', $value) : $value }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('events.registrations.update', ['event' => $event, 'registration' => $registration]) }}" method="POST" class="d-flex align-items-center gap-1">
                            @csrf
                            @method('PATCH')
                            <input type="number" name="seed" value="{{ $registration->seed }}" placeholder="Seed" class="form-control form-control-sm" style="width: 80px;" min="1">
                            <select name="status" class="form-select form-select-sm w-auto">
                                <option value="pending" @selected(($registration->status ?? 'pending') === 'pending')>Pending</option>
                                <option value="approved" @selected(($registration->status ?? 'pending') === 'approved')>Approved</option>
                                <option value="rejected" @selected(($registration->status ?? 'pending') === 'rejected')>Rejected</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
