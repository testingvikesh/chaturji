<?php

namespace App\Support;

class PrincipalReportCatalog
{
    /**
     * @return array<int, array{key: string, title: string, description: string, reports: array<int, array<string, mixed>>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'people',
                'title' => 'People on your standards',
                'description' => 'Teachers and students allotted to your standards',
                'reports' => [
                    [
                        'title' => 'Allotted Teachers',
                        'description' => 'Teachers with subjects on your standards',
                        'route' => 'principal.reports.allotted-teachers',
                        'match' => ['principal.reports.allotted-teachers'],
                        'badge' => 'Print',
                    ],
                    [
                        'title' => 'Allotted Students',
                        'description' => 'Students in your standards — add, edit, send login mail',
                        'route' => 'principal.reports.allotted-students',
                        'match' => ['principal.reports.allotted-students', 'principal.students.*'],
                        'badge' => 'Print',
                    ],
                ],
            ],
            [
                'key' => 'access',
                'title' => 'Access & sessions',
                'description' => 'Logins and sessions for your standards only',
                'reports' => [
                    [
                        'title' => 'Student Login Report',
                        'description' => 'Student success / failed logins (your standards)',
                        'route' => 'principal.reports.student-logins',
                        'match' => ['principal.reports.student-logins'],
                        'badge' => 'Print',
                    ],
                    [
                        'title' => 'Teacher Login Report',
                        'description' => 'Teacher logins for teachers on your standards',
                        'route' => 'principal.reports.teacher-logins',
                        'match' => ['principal.reports.teacher-logins'],
                        'badge' => 'Print',
                    ],
                    [
                        'title' => 'Session Report',
                        'description' => 'Active and recent sessions for your standards',
                        'route' => 'principal.reports.sessions',
                        'match' => ['principal.reports.sessions'],
                        'badge' => 'Print',
                    ],
                ],
            ],
            [
                'key' => 'teaching',
                'title' => 'Teaching work',
                'description' => 'Logout work reports and student work activity',
                'reports' => [
                    [
                        'title' => 'Syllabus Progress',
                        'description' => 'Teacher subjects · topics complete / remain · % syllabus',
                        'route' => 'principal.reports.syllabus-progress',
                        'match' => ['principal.reports.syllabus-progress'],
                        'badge' => 'Print',
                    ],
                    [
                        'title' => 'Teacher Click Report',
                        'description' => 'Teacher-wise and date-wise topic clicks with total points',
                        'route' => 'principal.reports.topic-clicks',
                        'match' => ['principal.reports.topic-clicks'],
                        'badge' => 'Print',
                    ],
                    [
                        'title' => 'Logout Work Reports',
                        'description' => 'Teacher period / chapter / topic reports before logout',
                        'route' => 'principal.reports.logout-reports',
                        'match' => ['principal.reports.logout-reports', 'principal.reports.logout-reports.show'],
                        'badge' => 'Print',
                    ],
                    [
                        'title' => 'Student Work Report',
                        'description' => 'Logins + exam / homework work done on your standards',
                        'route' => 'principal.reports.student-work',
                        'match' => ['principal.reports.student-work'],
                        'badge' => 'Print',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, route: string, match: array<int, string>}>
     */
    public static function navItems(): array
    {
        $items = [
            [
                'title' => 'All Reports',
                'route' => 'principal.reports.index',
                'match' => ['principal.reports.index'],
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

    public static function isReportsSidebarActive(): bool
    {
        // These have their own sidebar links under Reports.
        if (request()->routeIs(
            'principal.reports.allotted-teachers',
            'principal.reports.allotted-students',
            'principal.students.*'
        )) {
            return false;
        }

        foreach (self::navItems() as $item) {
            if (request()->routeIs(...$item['match'])) {
                return true;
            }
        }

        return false;
    }
}
