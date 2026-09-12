<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Standard;
use App\Models\Subject;
use App\Support\SlugHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Standard $standard): View
    {
        return view('admin.subjects.index', [
            'standard' => $standard,
            'subjects' => $standard->subjects()->withCount('chapters')->get(),
        ]);
    }

    public function create(Standard $standard): View
    {
        return view('admin.subjects.create', compact('standard'));
    }

    public function store(Request $request, Standard $standard)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $standard->subjects()->create([
            'name' => $validated['name'],
            'slug' => SlugHelper::unique($validated['name'], fn ($slug) => Subject::where('standard_id', $standard->id)->where('slug', $slug)->exists()),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.standards.subjects.index', $standard)->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject): View
    {
        $subject->load('standard');

        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $subject->update([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? $subject->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.standards.subjects.index', $subject->standard)->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $standard = $subject->standard;
        $subject->delete();

        return redirect()->route('admin.standards.subjects.index', $standard)->with('success', 'Subject deleted successfully.');
    }
}
