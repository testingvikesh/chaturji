<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('front.home');
    }

    public function about(): View
    {
        return view('front.about');
    }

    public function student(): View
    {
        return view('front.student');
    }

    public function teacher(): View
    {
        return view('front.teacher');
    }

    public function contact(): View
    {
        return view('front.contact');
    }

    public function contactStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Store or email contact messages here when mail is configured.
        unset($validated);

        return redirect()
            ->route('contact')
            ->with('success', 'Thank you! Your message has been sent successfully. We will contact you soon.');
    }
}
