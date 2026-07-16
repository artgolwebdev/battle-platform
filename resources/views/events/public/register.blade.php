@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Register for {{ $event->title }}</h1>

    <form method="POST" action="{{ route('events.public.register', $event) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        </div>

        @if($event->categories->isNotEmpty())
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category_id" id="category_select" class="form-select" required>
                    <option value="">Select a category</option>
                    @foreach($event->categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @foreach($event->registrationFields as $field)
            <div class="mb-3 dynamic-field" data-category="{{ $field->event_category_id }}">
                <label class="form-label">{{ ucfirst(str_replace('_', ' ', $field->field_name)) }}</label>
                @if($field->field_type === 'select')
                    <select name="fields[{{ $field->field_name }}]" class="form-select" @if($field->required) data-required="true" @endif>
                        <option value="">Choose one</option>
                        @foreach($field->options ?? [] as $option)
                            <option value="{{ $option }}" @selected(old('fields.' . $field->field_name) == $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="fields[{{ $field->field_name }}]" class="form-control" value="{{ old('fields.' . $field->field_name) }}" @if($field->required) data-required="true" @endif>
                @endif
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_select');
    const dynamicFields = document.querySelectorAll('.dynamic-field');

    function updateFields() {
        const selectedCategoryId = categorySelect ? categorySelect.value : null;

        dynamicFields.forEach(fieldDiv => {
            const fieldCategory = fieldDiv.getAttribute('data-category');
            const input = fieldDiv.querySelector('input, select');
            let shouldShow = ! categorySelect || fieldCategory === selectedCategoryId;

            if (shouldShow) {
                fieldDiv.style.display = 'block';
                if (input && input.hasAttribute('data-required')) {
                    input.setAttribute('required', 'required');
                }
            } else {
                fieldDiv.style.display = 'none';
                if (input) {
                    input.removeAttribute('required');
                }
            }
        });
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', updateFields);
    }

    updateFields();
});
</script>
@endsection