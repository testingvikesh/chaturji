<?php

namespace App\Support;

use App\Models\Material;
use App\Models\SchoolPeriod;
use App\Models\TeacherSubject;
use App\Models\TeacherTimetable;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TeacherTimetableService
{
    public function weekdayFor(?CarbonInterface $date = null): int
    {
        return ($date ?? now())->dayOfWeekIso;
    }

    /**
     * @return Collection<int, TeacherTimetable>
     */
    public function forTeacherOnDate(int $teacherId, ?CarbonInterface $date = null): Collection
    {
        $weekday = $this->weekdayFor($date);

        return TeacherTimetable::query()
            ->with([
                'period',
                'standard:id,name,slug',
                'subject:id,name,standard_id',
            ])
            ->active()
            ->forTeacher($teacherId)
            ->forWeekday($weekday)
            ->whereHas('period', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn (TeacherTimetable $row) => [
                $row->period?->sort_order ?? 999,
                $row->period?->period_no ?? 999,
            ])
            ->values();
    }

    /**
     * @return Collection<int, TeacherTimetable>
     */
    public function weekForTeacher(int $teacherId): Collection
    {
        return TeacherTimetable::query()
            ->with([
                'period',
                'standard:id,name,slug',
                'subject:id,name,standard_id',
            ])
            ->active()
            ->forTeacher($teacherId)
            ->whereHas('period', fn ($q) => $q->where('is_active', true))
            ->orderBy('weekday')
            ->get()
            ->sortBy(fn (TeacherTimetable $row) => [
                $row->weekday,
                $row->period?->sort_order ?? 999,
                $row->period?->period_no ?? 999,
            ])
            ->values();
    }

    /**
     * Logout-report options: today's timetable slots when set, else TeacherSubject assignments.
     *
     * @return array{source: string, options: Collection<int, array<string, mixed>>}
     */
    public function logoutReportOptions(User $teacher, ?CarbonInterface $date = null): array
    {
        $slots = $this->forTeacherOnDate($teacher->id, $date);

        if ($slots->isNotEmpty()) {
            $options = $slots->map(function (TeacherTimetable $row) {
                $medium = Material::normalizeMedium($row->medium) ?: $row->medium;

                return [
                    'key' => $row->assignmentKey(),
                    'timetable_id' => $row->id,
                    'period_id' => $row->period_id,
                    'period_label' => $row->period?->displayLabel(),
                    'section' => $row->section,
                    'medium' => $medium,
                    'medium_label' => $row->mediumLabel(),
                    'standard_id' => $row->standard_id,
                    'standard_slug' => $row->standard?->slug ?: $row->standard?->name,
                    'standard_name' => $row->standard?->name,
                    'subject_id' => $row->subject_id,
                    'subject_name' => $row->subject?->name,
                    'label' => $row->optionLabel(),
                    'from_timetable' => true,
                ];
            });

            return ['source' => 'timetable', 'options' => $options];
        }

        $assignments = TeacherSubject::query()
            ->with(['standard:id,name,slug', 'subject:id,name,standard_id'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('medium')
            ->orderBy('standard_id')
            ->get();

        $options = $assignments->map(function (TeacherSubject $row) {
            $medium = Material::normalizeMedium($row->medium) ?: $row->medium;
            $standard = $row->standard;
            $subject = $row->subject;
            if (! $standard || ! $subject) {
                return null;
            }

            return [
                'key' => $medium.'|'.$standard->id.'|'.$subject->id,
                'timetable_id' => null,
                'period_id' => null,
                'period_label' => null,
                'section' => null,
                'medium' => $medium,
                'medium_label' => \App\Models\Standard::MEDIUMS[$medium] ?? ucfirst((string) $medium),
                'standard_id' => $standard->id,
                'standard_slug' => $standard->slug ?: $standard->name,
                'standard_name' => $standard->name,
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'label' => (\App\Models\Standard::MEDIUMS[$medium] ?? ucfirst((string) $medium)).' · '.$standard->name.' · '.$subject->name,
                'from_timetable' => false,
            ];
        })->filter()->values();

        return ['source' => 'subjects', 'options' => $options];
    }

    /**
     * Ensure teacher_subjects row exists so Books / settings stay in sync.
     */
    public function syncTeacherSubject(TeacherTimetable $slot): void
    {
        $medium = Material::normalizeMedium($slot->medium) ?: $slot->medium;

        TeacherSubject::query()->firstOrCreate([
            'teacher_id' => $slot->teacher_id,
            'medium' => $medium,
            'standard_id' => $slot->standard_id,
            'subject_id' => $slot->subject_id,
        ]);
    }

    /**
     * @return Collection<int, SchoolPeriod>
     */
    public function activePeriods(): Collection
    {
        return SchoolPeriod::query()->active()->ordered()->get();
    }

    /**
     * Required period count for a teacher on a date (0 if no timetable set).
     */
    public function requiredCountForTeacher(int $teacherId, ?CarbonInterface $date = null): int
    {
        return $this->forTeacherOnDate($teacherId, $date)->count();
    }
}
