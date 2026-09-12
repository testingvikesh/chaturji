<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Subject;
use App\Support\SlugHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChapterController extends Controller
{
    public function index(Subject $subject): View
    {
        $subject->load('standard');

        return view('admin.chapters.index', [
            'subject' => $subject,
            'chapters' => $subject->chapters()->withCount('topics')->with('content')->get(),
        ]);
    }

    public function create(Subject $subject): View
    {
        $subject->load('standard');

        return view('admin.chapters.create', compact('subject'));
    }

    public function store(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $subject->chapters()->create([
            'name' => $validated['name'],
            'slug' => SlugHelper::unique($validated['name'], fn ($slug) => Chapter::where('subject_id', $subject->id)->where('slug', $slug)->exists()),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.subjects.chapters.index', $subject)->with('success', 'Chapter created successfully.');
    }

    public function edit(Chapter $chapter): View
    {
        $chapter->load('subject.standard');

        return view('admin.chapters.edit', compact('chapter'));
    }

    public function update(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $chapter->update([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? $chapter->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.subjects.chapters.index', $chapter->subject)->with('success', 'Chapter updated successfully.');
    }

    public function destroy(Chapter $chapter)
    {
        $subject = $chapter->subject;
        $chapter->delete();

        return redirect()->route('admin.subjects.chapters.index', $subject)->with('success', 'Chapter deleted successfully.');
    }
}
