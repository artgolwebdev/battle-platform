@extends('layouts.app')

@section('content')
<div class="container py-4">
    <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Events</a>
    <h1 class="mb-1">{{ $event->title }}</h1>
    <p class="text-muted mb-0">{{ $event->location }}</p>

    @if(session('status'))
        <div class="alert alert-success mt-3" role="alert">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mt-3" role="alert">
            <strong>Please review the highlighted form errors.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->getFirstMediaUrl('banner'))
        <img src="{{ $event->getFirstMediaUrl('banner', 'thumb') }}" alt="{{ $event->title }} banner" class="img-fluid rounded-3 mb-3">
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('events.registrations.index', $event) }}" class="btn btn-outline-primary">Manage Registrations</a>
        <a href="{{ route('events.bracket.show', $event) }}" class="btn btn-primary">Tournament Bracket</a>
    </div>

    <div class="card mb-4 shadow-sm border-0 rounded-4">
        <div class="card-body">
            <p class="mb-2">{{ $event->description }}</p>
            <p class="mb-1"><strong>Starts:</strong> {{ $event->start_date?->format('M d, Y H:i') ?? 'TBD' }}</p>
            <p class="mb-1"><strong>Ends:</strong> {{ $event->end_date?->format('M d, Y H:i') ?? 'TBD' }}</p>
            <p class="mb-0"><strong>Registration Open:</strong> {{ $event->registration_open ? 'Yes' : 'No' }}</p>
        </div>
    </div>

    <div class="card mb-4 shadow-sm border-0 rounded-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <span class="fw-semibold">Event Categories</span>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">Add Category</button>
        </div>
        <div class="card-body">
            @if($event->categories->isEmpty())
                <div class="alert alert-info mb-0">No categories created yet. Add one before configuring category-specific registration fields.</div>
            @else
                <div class="row g-3">
                    @foreach($event->categories as $category)
                        <div class="col-12 col-lg-6">
                            <div class="border rounded-4 p-3 h-100">
                                <div class="d-flex justify-content-between gap-3 mb-2">
                                    <div>
                                        <h2 class="h6 mb-1">{{ $category->name }}</h2>
                                        @if($category->description)
                                            <p class="text-muted small mb-0">{{ $category->description }}</p>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-column gap-1 align-items-end">
                                        <span class="badge bg-secondary">{{ $category->registrations_count }} registrations</span>
                                        <span class="badge bg-light text-dark border">{{ $category->battles_count }} battles</span>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-dark">Phase: {{ ucfirst($category->current_phase) }}</span>
                                    <span class="badge bg-info text-dark">{{ $category->has_prelims ? 'Uses prelims' : 'Skips prelims' }}</span>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal-{{ $category->id }}">Edit</button>
                                    <a href="{{ route('events.categories.prelims.show', [$event, $category]) }}" class="btn btn-sm btn-outline-secondary">Prelims Queue</a>
                                    @if($category->current_phase === 'registration')
                                        @if($category->has_prelims)
                                            <form action="{{ route('events.categories.prelims.start', [$event, $category]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Start Prelims</button>
                                            </form>
                                        @else
                                            <form action="{{ route('events.categories.prelims.complete', [$event, $category]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Generate Bracket</button>
                                            </form>
                                        @endif
                                    @elseif($category->current_phase === 'prelims')
                                        <form action="{{ route('events.categories.prelims.complete', [$event, $category]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Complete Prelims</button>
                                        </form>
                                    @endif
                                    @if($category->registrations_count > 0 || $category->battles_count > 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger" disabled title="This category still has dependent registrations or battles.">Delete locked</button>
                                    @else
                                        <form action="{{ route('events.categories.destroy', [$event, $category]) }}" method="POST" onsubmit="return confirm('Delete category {{ $category->name }}? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    @endif
                                </div>

                                @if($category->registrations_count > 0 || $category->battles_count > 0)
                                    <div class="alert alert-warning mt-3 mb-0 py-2 small">Deletion is blocked until dependent registrations and battles are removed or reassigned.</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <span class="fw-semibold">Registration Fields</span>
            @if($event->categories->isEmpty())
                <span class="text-muted small">Create a category before adding fields.</span>
            @else
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createFieldModal">Add Field</button>
            @endif
        </div>
        <div class="card-body">
            @if($event->registrationFields->isEmpty())
                <p class="text-muted mb-0">No custom registration fields created yet. (Name and Email are collected by default).</p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($event->registrationFields as $field)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong>{{ $field->field_name }}</strong> <span class="badge bg-secondary">{{ $field->field_type }}</span>
                                @if($field->required)
                                    <span class="badge bg-danger">Required</span>
                                @endif
                                <span class="badge bg-info text-dark">Category: {{ $field->category?->name ?? 'Unassigned' }}</span>
                                @if($field->field_type === 'select' && $field->options)
                                    <br><small class="text-muted">Options: {{ implode(', ', $field->options) }}</small>
                                @endif
                            </div>
                            <form action="{{ route('events.fields.destroy', [$event, $field]) }}" method="POST" onsubmit="return confirm('Delete this registration field?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('events.categories.store', $event) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_modal" value="create">
                    <div class="mb-3">
                        <label for="create_category_name" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="create_category_name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="create_category_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="create_category_description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="create_has_prelims" name="has_prelims" @checked(old('has_prelims'))>
                        <label class="form-check-label" for="create_has_prelims">Uses prelims</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($event->categories as $category)
    <div class="modal fade" id="editCategoryModal-{{ $category->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('events.categories.update', [$event, $category]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="editing_category_id" value="{{ $category->id }}">
                        <div class="mb-3">
                            <label for="category_name_{{ $category->id }}" class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="category_name_{{ $category->id }}" name="name" value="{{ old('editing_category_id') == $category->id ? old('name') : $category->name }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="category_description_{{ $category->id }}" class="form-label">Description (Optional)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="category_description_{{ $category->id }}" name="description" rows="3">{{ old('editing_category_id') == $category->id ? old('description') : $category->description }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="edit_has_prelims_{{ $category->id }}" name="has_prelims" @checked(old('editing_category_id') == $category->id ? old('has_prelims', $category->has_prelims) : $category->has_prelims)>
                            <label class="form-check-label" for="edit_has_prelims_{{ $category->id }}">Uses prelims</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="createFieldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('events.fields.store', $event) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Registration Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($event->categories->isEmpty())
                        <div class="alert alert-warning mb-0">Create a category before adding a registration field.</div>
                    @else
                        <div class="mb-3">
                            <label for="field_name" class="form-label">Field Name</label>
                            <input type="text" class="form-control" id="field_name" name="field_name" placeholder="e.g. Stage Name" required>
                        </div>
                        <div class="mb-3">
                            <label for="field_type" class="form-label">Field Type</label>
                            <select class="form-select" id="field_type" name="field_type" required>
                                <option value="text">Text Input</option>
                                <option value="select">Dropdown Select</option>
                            </select>
                        </div>
                        <div class="mb-3" id="optionsWrapper" style="display: none;">
                            <label for="options" class="form-label">Options (comma separated)</label>
                            <input type="text" class="form-control" id="options" name="options" placeholder="e.g. Beginner, Intermediate, Pro">
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                @foreach($event->categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="required" name="required" value="1" checked>
                            <label class="form-check-label" for="required">Required Field</label>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" @disabled($event->categories->isEmpty())>Save Field</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldTypeSelect = document.getElementById('field_type');
    const optionsWrapper = document.getElementById('optionsWrapper');

    if (fieldTypeSelect && optionsWrapper) {
        fieldTypeSelect.addEventListener('change', function() {
            optionsWrapper.style.display = this.value === 'select' ? 'block' : 'none';
        });

        if (fieldTypeSelect.value === 'select') {
            optionsWrapper.style.display = 'block';
        }
    }

    @if($errors->any() && old('category_modal') === 'create')
        const createCategoryModal = document.getElementById('createCategoryModal');
        if (createCategoryModal && window.bootstrap) {
            new bootstrap.Modal(createCategoryModal).show();
        }
    @endif

    @if(old('editing_category_id'))
        const editCategoryModal = document.getElementById('editCategoryModal-{{ old('editing_category_id') }}');
        if (editCategoryModal && window.bootstrap) {
            new bootstrap.Modal(editCategoryModal).show();
        }
    @endif
});
</script>
@endsection