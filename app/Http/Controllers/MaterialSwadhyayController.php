<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Subject;
use App\Support\MaterialSwadhyay;
use Illuminate\View\View;

class MaterialSwadhyayController extends Controller
{
    public function admin(string $medium, Subject $subject, Material $material): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return $this->page($subject, $material, 'admin', route('admin.materials.subject', [$medium, $subject]));
    }

    public function student(Subject $subject, Material $material): View
    {
        abort_unless(auth()->user()?->role === 'student', 403);

        return $this->page($subject, $material, 'student', route('student.subjects.show', $subject));
    }

    public function practice(Subject $subject, Material $material): View
    {
        abort_unless(auth()->user()?->role === 'student', 403);

        return $this->page($subject, $material, 'student', route('student.self-practice.subject', $subject));
    }

    public function teacher(Subject $subject, Material $material): View
    {
        abort_unless(auth()->user()?->role === 'teacher', 403);

        return $this->page($subject, $material, 'teacher', route('teacher.books.show', $subject));
    }

    private function page(Subject $subject, Material $material, string $panel, string $backUrl): View
    {
        $pack = MaterialSwadhyay::forMaterial($material);

        return view('materials.swadhyay', [
            'subject' => $subject,
            'material' => $material,
            'panel' => $panel,
            'backUrl' => $backUrl,
            'title' => $pack['title'],
            'sections' => $pack['sections'],
            'total' => $pack['total'],
        ]);
    }
}
