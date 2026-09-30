<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PrincipalLayout extends Component
{
    public function render(): View
    {
        return view('layouts.principal');
    }
}
