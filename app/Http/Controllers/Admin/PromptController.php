<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AiPrompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromptController extends Controller
{
    public function index(): View
    {
        return view('admin.prompts.index', [
            'prompts' => AiPrompt::all(),
        ]);
    }

    public function edit(string $prompt): View
    {
        $prompts = AiPrompt::all();
        abort_unless(isset($prompts[$prompt]), 404);

        return view('admin.prompts.edit', [
            'prompt' => $prompts[$prompt],
        ]);
    }

    public function update(Request $request, string $prompt): RedirectResponse
    {
        $keys = array_keys(config('prompts.catalog', []));
        abort_unless(in_array($prompt, $keys, true), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'key' => ['required', Rule::in($keys)],
        ]);

        Setting::updateOrCreate(
            ['key' => AiPrompt::settingKey($prompt)],
            ['value' => $validated['body']]
        );
        Setting::clearCache();

        return redirect()
            ->route('admin.prompts.edit', $prompt)
            ->with('success', 'Prompt saved. New checks will use this text.');
    }

    public function reset(string $prompt): RedirectResponse
    {
        $keys = array_keys(config('prompts.catalog', []));
        abort_unless(in_array($prompt, $keys, true), 404);

        Setting::query()->where('key', AiPrompt::settingKey($prompt))->delete();
        Setting::clearCache();

        return redirect()
            ->route('admin.prompts.edit', $prompt)
            ->with('success', 'Prompt reset to the default text.');
    }
}
