<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialQuestionEditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialQuestionEditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaterialQuestionEditLog::query()
            ->with(['teacher:id,name,mobile'])
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($inner) use ($search) {
                $inner->where('topic_title', 'like', '%'.$search.'%')
                    ->orWhere('subject_name', 'like', '%'.$search.'%')
                    ->orWhere('chapter_name', 'like', '%'.$search.'%')
                    ->orWhere('new_question_text', 'like', '%'.$search.'%')
                    ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($medium = $request->string('medium')->trim()->toString()) {
            $query->where('medium', $medium);
        }

        return view('admin.material-question-logs.index', [
            'logs' => $query->paginate(500)->withQueryString(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'medium' => $request->string('medium')->toString(),
            ],
        ]);
    }

    public function show(MaterialQuestionEditLog $materialQuestionEditLog): View
    {
        $materialQuestionEditLog->load(['teacher:id,name,mobile,email']);

        return view('admin.material-question-logs.show', [
            'log' => $materialQuestionEditLog,
        ]);
    }
}
