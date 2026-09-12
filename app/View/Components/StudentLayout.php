<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class StudentLayout extends Component
{
    public function render(): View
    {
        return view('layouts.student');
    }
}
