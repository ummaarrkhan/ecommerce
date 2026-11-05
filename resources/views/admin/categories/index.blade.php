@extends('layouts.admin')

@section('title', 'Category Management')
@section('page_title', 'Category Taxonomy')

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Categories</h3>
            <p class="text-muted small mb-0">Organize merchandise departments and hierarchical collections</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white border text-dark px-3 py-2 rounded-pill small">
                {{ $categories->total() }} Total Categories
            </span>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-store-primary btn-store-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Category
            </a>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h5 class="fw-bold text-dark">Category Hierarchy</h5>
        <span class="text-muted small">Parent and Subcategory mapping</span>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Type / Level</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($category->parent_id)
                                    <span class="text-muted ms-3">&rdsh;</span>
                                    <div class="bg-light rounded p-1 text-secondary" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-tag small"></i>
                                    </div>
                                    <span class="fw-semibold text-dark">{{ $category->name }}</span>
                                @else
                                    <div class="bg-primary text-white rounded p-1" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-folder-fill small"></i>
                                    </div>
                                    <span class="fw-bold text-dark fs-6">{{ $category->name }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($category->parent)
                                <span class="badge bg-light text-dark border">
                                    Subcategory of {{ $category->parent->name }}
                                </span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                    Root Category
                                </span>
                            @endif
                        </td>
                        <td>
                            <code class="text-secondary small">{{ $category->slug }}</code>
                        </td>
                        <td>
                            @if ($category->is_active)
                                <span class="status-pill status-completed">Active</span>
                            @else
                                <span class="status-pill status-cancelled">Hidden</span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $category->created_at->format('M d, Y') }}
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-light border" title="Edit Category">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category? Associated items may become uncategorized.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger border" title="Delete Category">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-tags fs-1 mb-2 d-block opacity-50"></i>
                            <h6 class="fw-bold text-dark">No categories found</h6>
                            <p class="small text-muted mb-3">Add your first root collection or department.</p>
                            <a href="{{ route('admin.categories.create') }}" class="btn btn-store-primary btn-store-sm">
                                <i class="bi bi-plus-lg me-1"></i> Add Category
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($categories->hasPages())
        <div class="p-3 border-top d-flex justify-content-center">
            {{ $categories->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
