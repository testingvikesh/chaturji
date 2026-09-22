<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolPeriod;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SchoolPeriodController extends Controller
{
    public function index(): View
    {
        return view('admin.timetable.periods', [
            'periods' => SchoolPeriod::query()->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_no' => ['required', 'integer', 'min:1', 'max:20', 'unique:school_periods,period_no'],
            'name' => ['required', 'string', 'max:64'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $period = SchoolPeriod::query()->create([
            'period_no' => (int) $validated['period_no'],
            'name' => $validated['name'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? $validated['period_no']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLogger::log('admin.period.create', 'Created school period '.$period->name, $period);

        return back()->with('success', 'Period added.');
    }

    public function update(Request $request, SchoolPeriod $period): RedirectResponse
    {
        $validated = $request->validate([
            'period_no' => ['required', 'integer', 'min:1', 'max:20', Rule::unique('school_periods', 'period_no')->ignore($period->id)],
            'name' => ['required', 'string', 'max:64'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $period->update([
            'period_no' => (int) $validated['period_no'],
            'name' => $validated['name'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? $validated['period_no']),
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLogger::log('admin.period.update', 'Updated school period '.$period->name, $period);

        return back()->with('success', 'Period updated.');
    }

    public function destroy(SchoolPeriod $period): RedirectResponse
    {
        if ($period->timetables()->exists()) {
            return back()->with('error', 'Cannot delete period used in teacher timetables. Deactivate it instead.');
        }

        $name = $period->name;
        $period->delete();
        ActivityLogger::log('admin.period.delete', 'Deleted school period '.$name);

        return back()->with('success', 'Period deleted.');
    }
}
