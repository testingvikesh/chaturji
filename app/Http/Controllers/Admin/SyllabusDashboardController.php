<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminSyllabusReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SyllabusDashboardController extends Controller
{
    public function index(Request $request, AdminSyllabusReport $report): View
    {
        $payload = $report->build([
            'medium' => $request->string('medium')->toString(),
            'standard' => $request->string('standard')->toString(),
            'subject_id' => $request->input('subject_id'),
            'teacher_id' => $request->input('teacher_id'),
            'chapter_id' => $request->input('chapter_id'),
            'topic_id' => $request->input('topic_id'),
            'status' => $request->string('status')->toString(),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->string('search')->toString(),
            'sort' => $request->string('sort')->toString(),
            'dir' => $request->string('dir')->toString(),
            'view' => $request->string('view')->toString() ?: 'overview',
        ]);

        return view('admin.dashboard-syllabus', $payload);
    }
}
