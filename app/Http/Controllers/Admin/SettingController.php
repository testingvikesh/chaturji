<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MailSetupTestMail;
use App\Models\Setting;
use App\Support\EmailLogRecorder;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.settings.edit', [
            'groups' => config('settings.groups', []),
            'settings' => Setting::allCached(),
            'activeTab' => $request->string('tab')->toString() ?: 'general',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $keys = Setting::keys();
        $rules = [];

        foreach ($keys as $key) {
            $rules[$key] = ['nullable', 'string', 'max:5000'];
        }

        $validated = $request->validate($rules);
        $passwordKeys = $this->passwordKeys();

        foreach ($passwordKeys as $key) {
            if (array_key_exists($key, $validated) && trim((string) $validated[$key]) === '') {
                unset($validated[$key]);
            }
        }

        Setting::setMany($validated);
        MailConfig::apply();

        $tab = $request->string('active_tab')->toString() ?: 'general';

        return redirect()
            ->route('admin.settings.edit', ['tab' => $tab])
            ->with('success', 'Website settings updated successfully.');
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $email = $request->validate([
            'test_email' => ['required', 'email'],
        ])['test_email'];

        try {
            MailConfig::apply();
            Mail::to($email)->send(new MailSetupTestMail);
        } catch (Throwable $e) {
            EmailLogRecorder::markLastFailed($e, $email, 'Mail setup test', MailSetupTestMail::class);

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'mail'])
                ->with('error', 'Test mail failed: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.settings.edit', ['tab' => 'mail'])
            ->with('success', 'Test mail sent to '.$email.'.');
    }

    /**
     * @return list<string>
     */
    private function passwordKeys(): array
    {
        $keys = [];

        foreach (config('settings.groups', []) as $group) {
            foreach ($group['fields'] ?? [] as $key => $field) {
                if (($field['type'] ?? '') === 'password') {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }
}
