@extends('layouts.admin')

@section('title', 'Edit Category: ' . $category->name)
@section('page_title', 'Edit Category')

@section('content')
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <a href="{{ route('admin.categories.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back to Categories
            </a>
            <h3 class="fw-bold text-dark mt-2 mb-0">Edit Category: {{ $category->name }}</h3>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border rounded-3 p-4 bg-white shadow-xs">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-secondary">Category Name *</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $category->name) }}"
                        maxlength="150"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="parent_id" class="form-label small fw-semibold text-secondary">Parent Category</label>
                    <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                        <option value="">None (Top-Level Root Category)</option>
                        @foreach ($parentCategories as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold text-secondary">Description (Optional)</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="3"
                        class="form-control @error('description') is-invalid @enderror"
                    >{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary d-block">Visibility Status</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="is_active" id="active1" value="1" {{ old('is_active', $category->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="active1">Active (Visible)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="is_active" id="active0" value="0" {{ old('is_active', $category->is_active ? '1' : '0') == '0' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="active0">Hidden</label>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 pt-2 border-top">
                    <button type="submit" class="btn btn-store-primary px-4 fw-bold">
                        <i class="bi bi-check2 me-1"></i> Update Category
                    </button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-store-outline">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
