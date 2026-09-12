<?php

namespace App\Support;

use App\Models\Chapter;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Support\Collection;

class StudentSidebarTrail
{
    /**
     * @return Collection<int, array{label: string, url: string|null, current: bool}>
     */
    public static function resolve(): Collection
    {
        $route = request()->route();
        $name = $route?->getName();

        if (! $name || ! str_starts_with($name, 'student.')) {
            return collect();
        }

        return match ($name) {
            'student.dashboard' => collect([
                self::item('Dashboard', null, true),
            ]),
            'student.subjects.index' => collect([
                self::item('My Subjects', null, true),
            ]),
            'student.subjects.show' => self::subjectTrail(self::subjectFromRoute($route)),
            'student.chapters.show' => self::chapterTrail(
                self::subjectFromRoute($route),
                self::chapterFromRoute($route),
            ),
            'student.topics.show' => self::topicTrail(
                self::subjectFromRoute($route),
                self::topicFromRoute($route),
            ),
            'student.exams.index' => collect([
                self::item('Self Exam', null, true),
            ]),
            'student.exams.create' => collect([
                self::item('Self Exam', url('/student/exams'), false),
                self::item('Create', null, true),
            ]),
            'student.exams.show', 'student.exams.preview' => self::examTrail(self::examFromRoute($route)),
            'student.homework.index' => collect([
                self::item('Self Homework', null, true),
            ]),
            'student.homework.create' => collect([
                self::item('Self Homework', url('/student/homework'), false),
                self::item('Create', null, true),
            ]),
            'student.homework.show', 'student.homework.preview' => self::homeworkTrail(self::homeworkFromRoute($route)),
            'student.self-practice.index' => collect([
                self::item('Self Practice', null, true),
            ]),
            'student.self-practice.subject' => self::selfPracticeSubjectTrail(self::subjectFromRoute($route)),
            'student.self-practice.chapter' => self::selfPracticeChapterTrail(
                self::subjectFromRoute($route),
                self::chapterFromRoute($route),
            ),
            'student.notifications.index' => collect([
                self::item('Notifications', null, true),
            ]),
            'student.profile.edit' => collect([
                self::item('Edit Profile', null, true),
            ]),
            'student.change-password' => collect([
                self::item('Change Password', null, true),
            ]),
            default => collect(),
        };
    }

    /**
     * @return Collection<int, array{label: string, url: string|null, current: bool}>
     */
    private static function subjectTrail(?Subject $subject): Collection
    {
        if (! $subject) {
            return collect();
        }

        return collect([
            self::item('My Subjects', route('student.subjects.index'), false),
            self::item($subject->name, null, true),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, url: string|null, current: bool}>
     */
    private static function chapterTrail(?Subject $subject, ?Chapter $chapter): Collection
    {
        if (! $subject || ! $chapter) {
            return collect();
        }

        return collect([
            self::item('My Subjects', route('student.subjects.index'), false),
            self::item($subject->name, route('student.subjects.show', $subject), false),
            self::item($chapter->name, null, true),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, url: string|null, current: bool}>
     */
    private static function topicTrail(?Subject $subject, ?Topic $topic): Collection
    {
        if (! $subject || ! $topic) {
            return collect();
        }

        $chapter = $topic->relationLoaded('chapter')
            ? $topic->chapter
            : $topic->chapter()->first();

        if (! $chapter) {
            return self::subjectTrail($subject);
        }

        return collect([
            self::item('My Subjects', route('student.subjects.index'), false),
            self::item($subject->name, route('student.subjects.show', $subject), false),
            self::item($chapter->name, route('student.chapters.show', [$subject, $chapter]), false),
            self::item($topic->name, null, true),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, url: string|null, current: bool}>
     */
    private static function selfPracticeSubjectTrail(?Subject $subject): Collection
    {
        if (! $subject) {
            return collect();
        }

        return collect([
            self::item('Self Practice', route('student.self-practice.index'), false),
            self::item($subject->name, null, true),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, url: string|null, current: bool}>
     */
    private static function selfPracticeChapterTrail(?Subject $subject, ?Chapter $chapter): Collection
    {
        if (! $subject || ! $chapter) {
            return collect();
        }

        return collect([
            self::item('Self Practice', route('student.self-practice.index'), false),
            self::item($subject->name, route('student.self-practice.subject', $subject), false),
            self::item($chapter->name, null, true),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, url: string|null, current: bool}>
     */
    private static function examTrail(?Exam $exam): Collection
    {
        if (! $exam) {
            return collect([
                self::item('Self Exam', null, true),
            ]);
        }

        return collect([
            self::item('Self Exam', url('/student/exams'), false),
            self::item($exam->title ?: 'Exam #'.$exam->id, null, true),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, url: string|null, current: bool}>
     */
    private static function homeworkTrail(?Homework $homework): Collection
    {
        if (! $homework) {
            return collect([
                self::item('Self Homework', null, true),
            ]);
        }

        return collect([
            self::item('Self Homework', url('/student/homework'), false),
            self::item($homework->title ?: 'Homework #'.$homework->id, null, true),
        ]);
    }

    /**
     * @return array{label: string, url: string|null, current: bool}
     */
    private static function item(string $label, ?string $url, bool $current): array
    {
        return [
            'label' => $label,
            'url' => $url,
            'current' => $current,
        ];
    }

    private static function subjectFromRoute($route): ?Subject
    {
        $subject = $route->parameter('subject');

        return $subject instanceof Subject ? $subject : Subject::query()->find($subject);
    }

    private static function chapterFromRoute($route): ?Chapter
    {
        $chapter = $route->parameter('chapter');

        return $chapter instanceof Chapter ? $chapter : Chapter::query()->find($chapter);
    }

    private static function topicFromRoute($route): ?Topic
    {
        $topic = $route->parameter('topic');

        if ($topic instanceof Topic) {
            $topic->loadMissing('chapter');

            return $topic;
        }

        return Topic::query()->with('chapter')->find($topic);
    }

    private static function examFromRoute($route): ?Exam
    {
        $exam = $route->parameter('exam');

        return $exam instanceof Exam ? $exam : Exam::query()->find($exam);
    }

    private static function homeworkFromRoute($route): ?Homework
    {
        $homework = $route->parameter('homework');

        return $homework instanceof Homework ? $homework : Homework::query()->find($homework);
    }
}
