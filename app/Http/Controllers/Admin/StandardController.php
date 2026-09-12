<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Standard;
use App\Support\SlugHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StandardController extends Controller
{
    public function index(): View
    {
        $standards = Standard::query()
            ->orderedByNumber()
            ->orderBy('name')
            ->get()
            ->map(function (Standard $standard) {
                foreach (Material::reportCountsForStandard($standard) as $key => $value) {
                    $standard->setAttribute($key, $value);
                }

                return $standard;
            });

        return view('admin.standards.index', [
            'standards' => $standards,
        ]);
    }

    public function create(): View
    {
        return view('admin.standards.create', [
            'mediums' => Standard::MEDIUMS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slugBase = $validated['name'].'-'.$validated['medium'];

        Standard::create([
            'name' => $validated['name'],
            'slug' => SlugHelper::unique($slugBase, fn ($slug) => Standard::where('slug', $slug)->exists()),
            'medium' => $validated['medium'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.standards.index')->with('success', 'Standard created successfully.');
    }

    public function edit(Standard $standard): View
    {
        return view('admin.standards.edit', [
            'standard' => $standard,
            'mediums' => Standard::MEDIUMS,
        ]);
    }

    public function update(Request $request, Standard $standard)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'medium' => ['required', Rule::in(array_keys(Standard::MEDIUMS))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $standard->update([
            'name' => $validated['name'],
            'medium' => $validated['medium'],
            'sort_order' => $validated['sort_order'] ?? $standard->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.standards.index')->with('success', 'Standard updated successfully.');
    }

    public function destroy(Standard $standard)
    {
        $standard->delete();

        return redirect()->route('admin.standards.index')->with('success', 'Standard deleted successfully.');
    }
}
