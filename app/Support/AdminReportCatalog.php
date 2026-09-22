<?php

namespace App\Support;

class AdminReportCatalog
{
    /**
     * All admin reports grouped for the Reports module hub.
     *
     * @return array<int, array{key: string, title: string, description: string, reports: array<int, array<string, mixed>>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'teaching',
                'title' => 'Teaching & Syllabus',
                'description' => 'Daily teaching, materials, timetable work and logout reports',
                'reports' => [
                    [
                        'title' => 'Syllabus Dashboard',
                        'description' => 'Class / subject / teacher progress and daily compliance',
                        'route' => 'admin.dashboard.syllabus',
                        'match' => ['admin.dashboard.syllabus'],
                        'badge' => 'Live',
                    ],
                    [
                        'title' => 'Material Report',
                        'description' => 'Uploaded materials tree by medium and standard',
                        'route' => 'admin.dashboard.materials',
                        'match' => ['admin.dashboard.materials'],
                        'badge' => null,
                    ],
                    [
                        'title' => 'Logout Reports',
                        'description' => 'Teacher work report before logout (period · chapter · topics)',
                        'route' => 'admin.reports.logout-reports',
                        'match' => ['admin.reports.logout-reports', 'admin.reports.logout-reports.show'],
                        'badge' => null,
                    ],
                    [
                        'title' => 'Teacher Subjects',
                        'description' => 'Which teacher is assigned to which medium / standard / subject',
                        'route' => 'admin.reports.teacher-subjects',
                        'match' => ['admin.reports.teacher-subjects'],
                        'badge' => null,
                    ],
                    [
                        'title' => 'Chapter List',
                        'description' => 'Printable Medium · Standard · Subject · Chapter list',
                        'route' => 'admin.reports.chapter-list',
                        'match' => ['admin.reports.chapter-list'],
                        'badge' => 'Print',
                    ],
                    [
                        'title' => 'Teacher Summary',
                        'description' => 'Teacher counts, OTP and weekly login snapshot',
                        'route' => 'admin.teachers.report',
                        'match' => ['admin.teachers.report'],
                        'badge' => null,
                    ],
                    [
                        'title' => 'Student Summary',
                        'description' => 'Student registration and activity snapshot',
                        'route' => 'admin.students.report',
                        'match' => ['admin.students.report'],
                        'badge' => null,
                    ],
                ],
            ],
            [
                'key' => 'access',
                'title' => 'Access & Activity',
                'description' => 'Logins, sessions and platform activity trail',
                'reports' => [
                    [
                        'title' => 'Login Report',
                        'description' => 'Student and teacher login success / failed attempts',
                        'route' => 'admin.reports.logins',
                        'match' => ['admin.reports.logins'],
                        'badge' => null,
                    ],
                    [
                        'title' => 'Session Report',
                        'description' => 'Active and recent user sessions',
                        'route' => 'admin.reports.sessions',
                        'match' => ['admin.reports.sessions'],
                        'badge' => null,
                    ],
                    [
                        'title' => 'Activity Log',
                        'description' => 'Key platform actions with actor and details',
                        'route' => 'admin.reports.activity',
                        'match' => ['admin.reports.activity', 'admin.reports.activity.show'],
                        'badge' => null,
                    ],
                ],
            ],
            [
                'key' => 'support',
                'title' => 'Support & System',
                'description' => 'Tickets, question edits and outbound email trail',
                'reports' => [
                    [
                        'title' => 'Ticket Report',
                        'description' => 'Student and teacher support tickets',
                        'route' => 'admin.tickets.index',
                        'match' => ['admin.tickets.*'],
                        'badge' => null,
                    ],
                    [
                        'title' => 'Question Edits',
                        'description' => 'Teacher book question edit history',
                        'route' => 'admin.material-question-logs.index',
                        'match' => ['admin.material-question-logs.*'],
                        'badge' => null,
                    ],
                    [
                        'title' => 'Email Log',
                        'description' => 'Emails sent by the system (logout report, etc.)',
                        'route' => 'admin.email-logs.index',
                        'match' => ['admin.email-logs.*'],
                        'badge' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * Flat list for the inner reports sub-navigation.
     *
     * @return array<int, array{title: string, route: string, match: array<int, string>}>
     */
    public static function navItems(): array
    {
        $items = [
            [
                'title' => 'All Reports',
                'route' => 'admin.reports.index',
                'match' => ['admin.reports.index'],
            ],
        ];

        foreach (self::groups() as $group) {
            foreach ($group['reports'] as $report) {
                $items[] = [
                    'title' => $report['title'],
                    'route' => $report['route'],
                    'match' => $report['match'],
                ];
            }
        }

        return $items;
    }

    public static function isReportsContext(): bool
    {
        foreach (self::navItems() as $item) {
            if (request()->routeIs(...$item['match'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sidebar highlight — exclude dashboard tabs that already have their own menu items.
     */
    public static function isReportsSidebarActive(): bool
    {
        if (request()->routeIs('admin.dashboard.materials', 'admin.dashboard.syllabus')) {
            return false;
        }

        return self::isReportsContext();
    }
}
