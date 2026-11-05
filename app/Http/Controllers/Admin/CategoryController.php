<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // Show all categories
    public function index()
    {
        $categories = Category::with('parent')
            ->orderByRaw('COALESCE(parent_id, id)')
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    // Show create category page
    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    // Save new category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('parent_id', null),
            ],
            'description' => 'nullable|string|max:5000',
            'is_active' => 'required|boolean',
        ]);

        // Create a slug from category name
        $slug = Str::slug($validated['name']);

        if ($slug == '') {
            $slug = 'category';
        }

        // Make sure slug is unique
        $originalSlug = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $validated['slug'] = $slug;

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    // Show edit category page
    public function edit(Category $category)
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    // Update category
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('parent_id', null),
                Rule::notIn([$category->id]),
            ],
            'description' => 'nullable|string|max:5000',
            'is_active' => 'required|boolean',
        ]);

        // Create slug from updated name
        $slug = Str::slug($validated['name']);

        if ($slug == '') {
            $slug = 'category';
        }

        // Check if slug already exists
        $originalSlug = $slug;
        $counter = 1;

        while (
            Category::where('slug', $slug)
                ->where('id', '!=', $category->id)
                ->exists()
        ) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $validated['slug'] = $slug;

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    // Delete category
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
