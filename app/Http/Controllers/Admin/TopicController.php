<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Topic;
use App\Support\SlugHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function index(Chapter $chapter): View
    {
        $chapter->load('subject.standard');

        return view('admin.topics.index', [
            'chapter' => $chapter,
            'topics' => $chapter->topics,
        ]);
    }

    public function create(Chapter $chapter): View
    {
        $chapter->load('subject.standard');

        return view('admin.topics.create', compact('chapter'));
    }

    public function store(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $chapter->topics()->create([
            'name' => $validated['name'],
            'slug' => SlugHelper::unique($validated['name'], fn ($slug) => Topic::where('chapter_id', $chapter->id)->where('slug', $slug)->exists()),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.chapters.topics.index', $chapter)->with('success', 'Topic created successfully.');
    }

    public function edit(Topic $topic): View
    {
        $topic->load('chapter.subject.standard');

        return view('admin.topics.edit', compact('topic'));
    }

    public function update(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $topic->update([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? $topic->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.chapters.topics.index', $topic->chapter)->with('success', 'Topic updated successfully.');
    }

    public function destroy(Topic $topic)
    {
        $chapter = $topic->chapter;
        $topic->delete();

        return redirect()->route('admin.chapters.topics.index', $chapter)->with('success', 'Topic deleted successfully.');
    }
}
