<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Support\TeacherClickReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClickReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('teacher.reports.topic-clicks', TeacherClickReport::build($request, (int) auth()->id()));
    }
}
