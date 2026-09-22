<?php

use App\Http\Controllers\Admin\SyllabusDashboardController;
use App\Http\Controllers\Admin\ChangePasswordController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\DataUploadController;
use App\Http\Controllers\Admin\PromptController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StandardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\ChapterOriginalPdfController;
use App\Http\Controllers\MaterialPdfController;
use App\Http\Controllers\Front\AuthController;
use App\Http\Controllers\Front\ForgotPasswordController;
use App\Http\Controllers\Front\ResetPasswordController;
use App\Http\Controllers\Front\PwaController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use App\Http\Controllers\Student\SelfExamPageController as StudentSelfExamPageController;
use App\Http\Controllers\Student\HomeworkController as StudentHomeworkController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\SelfPracticeController as StudentSelfPracticeController;
use App\Http\Controllers\Student\MaterialTopicController as StudentMaterialTopicController;
use App\Http\Controllers\Student\SubjectController as StudentSubjectController;
use App\Http\Controllers\Student\TopicController as StudentTopicController;
use App\Http\Controllers\Teacher\CurriculumController as TeacherCurriculumController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\ExamController as TeacherExamController;
use App\Http\Controllers\Teacher\HomeworkController as TeacherHomeworkController;
use App\Http\Controllers\Teacher\SubjectController as TeacherSubjectController;
use App\Http\Controllers\Teacher\TopicController as TeacherTopicController;
use App\Http\Controllers\Teacher\TodaysExamController;
use App\Http\Controllers\Teacher\DailySyllabusController;
use App\Http\Controllers\Teacher\TodaysTeachingController;
use App\Http\Controllers\Teacher\SettingController as TeacherSettingController;
use App\Http\Controllers\Teacher\BookController as TeacherBookController;
use App\Http\Controllers\Teacher\NotificationController as TeacherNotificationController;
use App\Http\Controllers\Teacher\ProfileController as TeacherProfileController;
use App\Http\Controllers\UserTicketController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\Cron\TeacherOtpCronController;
use Illuminate\Support\Facades\Route;

Route::get('/media/{path}', [PublicStorageController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

// Direct login is the only public front page
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'loginHub'])->name('home');
    Route::get('/login', [AuthController::class, 'loginHub'])->name('login');
    Route::get('/register', [AuthController::class, 'registerHub'])->name('register');

    Route::get('/student/login', [AuthController::class, 'studentLoginForm'])->name('student.login');
    Route::post('/student/login', [AuthController::class, 'studentLoginStore']);
    Route::get('/student/register', [AuthController::class, 'studentRegisterForm'])->name('student.register');
    Route::post('/student/register', [AuthController::class, 'studentRegisterStore']);
    Route::get('/student/register/success', fn () => app(AuthController::class)->registerSuccess('student'))->name('student.register.success');

    Route::get('/teacher/login', [AuthController::class, 'teacherLoginForm'])->name('teacher.login');
    Route::post('/teacher/login', [AuthController::class, 'teacherLoginStore']);
    Route::get('/teacher/register', [AuthController::class, 'teacherRegisterForm'])->name('teacher.register');
    Route::post('/teacher/register', [AuthController::class, 'teacherRegisterStore']);
    Route::get('/teacher/register/success', fn () => app(AuthController::class)->registerSuccess('teacher'))->name('teacher.register.success');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

// Old marketing pages removed — send everyone to login
Route::redirect('/about-us', '/')->name('about');
Route::redirect('/student', '/')->name('student');
Route::redirect('/teacher', '/')->name('teacher');
Route::redirect('/contact-us', '/')->name('contact');
Route::post('/contact-us', fn () => redirect()->route('home'))->name('contact.store');

Route::get('/cron/teacher-otp/{token}', TeacherOtpCronController::class)->name('cron.teacher-otp');

Route::get('/install', [PwaController::class, 'install'])->name('pwa.install');
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/chapter-content/{chapterContent}/original-pdf', [ChapterOriginalPdfController::class, 'show'])
        ->name('chapter-content.original-pdf');
    Route::get('/materials/{material}/textbook-pdf', [MaterialPdfController::class, 'show'])
        ->name('materials.textbook-pdf');
    Route::get('/materials/{material}/textbook-page/{page}', [MaterialPdfController::class, 'page'])
        ->where('page', '[A-Za-z0-9._-]+')
        ->name('materials.textbook-page');
});

Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/subjects', [StudentSubjectController::class, 'index'])->name('subjects.index');
    Route::get('/subjects/{subject}', [StudentSubjectController::class, 'show'])->name('subjects.show');
    Route::get('/subjects/{subject}/materials/{material}', [StudentMaterialTopicController::class, 'material'])->name('materials.show');
    Route::get('/subjects/{subject}/material-topics/{materialTopic}', [StudentMaterialTopicController::class, 'show'])->name('material-topics.show');
    Route::get('/subjects/{subject}/topics/{topic}', [StudentTopicController::class, 'show'])->name('topics.show');
    Route::get('/subjects/{subject}/chapters/{chapter}', [StudentTopicController::class, 'chapter'])->name('chapters.show');
    Route::get('/profile', [StudentProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [StudentProfileController::class, 'update'])->name('profile.update');
    Route::get('/change-password', [StudentProfileController::class, 'editPassword'])->name('change-password');
    Route::put('/change-password', [StudentProfileController::class, 'updatePassword'])->name('password.update');

    Route::get('/exams', [StudentSelfExamPageController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [StudentExamController::class, 'create'])->name('exams.create');
    Route::post('/exams', [StudentExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/question-counts', [StudentExamController::class, 'questionCounts'])->name('exams.question-counts');
    Route::post('/exams/paper-preview', [StudentExamController::class, 'paperPreview'])->name('exams.paper-preview');
    Route::get('/exams/{exam}/preview', [StudentExamController::class, 'preview'])->name('exams.preview');
    Route::get('/exams/{exam}/print', [StudentExamController::class, 'printPaper'])->name('exams.print');
    Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
    Route::post('/exams/{exam}/submit-answer-pdf', [StudentExamController::class, 'submitAnswerPdf'])->name('exams.submit-answer-pdf');

    Route::get('/homework', [StudentHomeworkController::class, 'index'])->name('homework.index');
    Route::get('/homework/create', [StudentHomeworkController::class, 'create'])->name('homework.create');
    Route::post('/homework', [StudentHomeworkController::class, 'store'])->name('homework.store');
    Route::get('/homework/question-counts', [StudentHomeworkController::class, 'questionCounts'])->name('homework.question-counts');
    Route::post('/homework/paper-preview', [StudentHomeworkController::class, 'paperPreview'])->name('homework.paper-preview');
    Route::get('/homework/{homework}/preview', [StudentHomeworkController::class, 'preview'])->name('homework.preview');
    Route::get('/homework/{homework}/print', [StudentHomeworkController::class, 'printPaper'])->name('homework.print');
    Route::get('/homework/{homework}', [StudentHomeworkController::class, 'show'])->name('homework.show');
    Route::post('/homework/{homework}/submit-answer-pdf', [StudentHomeworkController::class, 'submitAnswerPdf'])->name('homework.submit-answer-pdf');

    Route::get('/self-practice', [StudentSelfPracticeController::class, 'index'])->name('self-practice.index');
    Route::get('/self-practice/subjects/{subject}', [StudentSelfPracticeController::class, 'subject'])->name('self-practice.subject');
    Route::get('/self-practice/subjects/{subject}/materials/{material}', [StudentSelfPracticeController::class, 'material'])->name('self-practice.materials.show');
    Route::get('/self-practice/subjects/{subject}/material-topics/{materialTopic}', [StudentSelfPracticeController::class, 'materialTopic'])->name('self-practice.material-topics.show');
    Route::get('/self-practice/subjects/{subject}/chapters/{chapter}', [StudentSelfPracticeController::class, 'chapter'])->name('self-practice.chapter');

    Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('notifications.index');
    Route::match(['get', 'post'], '/notifications/{id}/read', [StudentNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [StudentNotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/tickets', [UserTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [UserTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [UserTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/options/subjects', [UserTicketController::class, 'subjects'])->name('tickets.subjects');
    Route::get('/tickets/options/chapters', [UserTicketController::class, 'chapters'])->name('tickets.chapters');
    Route::get('/tickets/{ticket}', [UserTicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [UserTicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    Route::post('/tickets/{ticket}/reply', [UserTicketController::class, 'reply'])->name('tickets.reply');
});

Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/books', [TeacherBookController::class, 'index'])->name('books.index');
    Route::get('/books/{subject}', [TeacherBookController::class, 'show'])->name('books.show');
    Route::get('/books/{subject}/materials/{material}', [TeacherBookController::class, 'material'])->name('books.materials.show');
    Route::get('/books/{subject}/topics/{materialTopic}', [TeacherBookController::class, 'topic'])->name('books.topics.show');
    Route::get('/settings', [TeacherSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [TeacherSettingController::class, 'update'])->name('settings.update');
    Route::delete('/settings/subjects/{teacherSubject}', [TeacherSettingController::class, 'destroySubject'])->name('settings.subjects.destroy');
    Route::delete('/settings/groups', [TeacherSettingController::class, 'destroyGroup'])->name('settings.groups.destroy');
    Route::get('/profile', [TeacherProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [TeacherProfileController::class, 'update'])->name('profile.update');
    Route::get('/change-password', [TeacherProfileController::class, 'editPassword'])->name('change-password');
    Route::put('/change-password', [TeacherProfileController::class, 'updatePassword'])->name('password.update');
    Route::get('/subjects/{subject}', [TeacherSubjectController::class, 'show'])->name('subjects.show');
    Route::get('/subjects/{subject}/topics/{topic}', [TeacherTopicController::class, 'show'])->name('subjects.topics.show');
    Route::get('/subjects/{subject}/chapters/{chapter}', [TeacherTopicController::class, 'chapter'])->name('subjects.chapters.show');

    Route::post('exams/preview', [TeacherExamController::class, 'preview'])->name('exams.preview');
    Route::post('homework/preview', [TeacherHomeworkController::class, 'preview'])->name('homework.preview');

    Route::resource('exams', TeacherExamController::class);
    Route::resource('homework', TeacherHomeworkController::class);

    Route::get('/todays-teaching', [TodaysTeachingController::class, 'index'])->name('todays-teaching.index');
    Route::get('/todays-teaching/create', [TodaysTeachingController::class, 'create'])->name('todays-teaching.create');
    Route::post('/todays-teaching', [TodaysTeachingController::class, 'store'])->name('todays-teaching.store');
    Route::get('/todays-teaching/homework-preview', [TodaysTeachingController::class, 'showHomeworkPreview'])->name('todays-teaching.homework-preview');
    Route::post('/todays-teaching/homework-preview', [TodaysTeachingController::class, 'buildHomeworkPreview'])->name('todays-teaching.homework-preview.build');
    Route::post('/todays-teaching/homework-confirm', [TodaysTeachingController::class, 'confirmHomework'])->name('todays-teaching.homework-confirm');
    Route::patch('/todays-teaching/{teachingLog}/status', [TodaysTeachingController::class, 'updateStatus'])->name('todays-teaching.status');
    Route::post('/todays-teaching/generate-homework', [TodaysTeachingController::class, 'generateHomework'])->name('todays-teaching.generate-homework');
    Route::delete('/todays-teaching/{teachingLog}', [TodaysTeachingController::class, 'destroy'])->name('todays-teaching.destroy');

    Route::get('/daily-syllabus', [DailySyllabusController::class, 'index'])->name('daily-syllabus.index');
    Route::get('/daily-syllabus/create', [DailySyllabusController::class, 'create'])->name('daily-syllabus.create');
    Route::post('/daily-syllabus', [DailySyllabusController::class, 'store'])->name('daily-syllabus.store');
    Route::get('/daily-syllabus/pending', [DailySyllabusController::class, 'pending'])->name('daily-syllabus.pending');
    Route::get('/daily-syllabus/progress', [DailySyllabusController::class, 'progress'])->name('daily-syllabus.progress');

    Route::get('/todays-exam', [TodaysExamController::class, 'index'])->name('todays-exam.index');
    Route::get('/todays-exam/create', [TodaysExamController::class, 'create'])->name('todays-exam.create');
    Route::post('/todays-exam', [TodaysExamController::class, 'store'])->name('todays-exam.store');
    Route::get('/todays-exam/exam-preview', [TodaysExamController::class, 'showExamPreview'])->name('todays-exam.exam-preview');
    Route::post('/todays-exam/exam-preview', [TodaysExamController::class, 'buildExamPreview'])->name('todays-exam.exam-preview.build');
    Route::post('/todays-exam/exam-confirm', [TodaysExamController::class, 'confirmExam'])->name('todays-exam.exam-confirm');
    Route::patch('/todays-exam/{examLog}/status', [TodaysExamController::class, 'updateStatus'])->name('todays-exam.status');
    Route::post('/todays-exam/generate-exam', [TodaysExamController::class, 'generateExam'])->name('todays-exam.generate-exam');
    Route::delete('/todays-exam/{examLog}', [TodaysExamController::class, 'destroy'])->name('todays-exam.destroy');

    Route::get('/curriculum/subjects', [TeacherCurriculumController::class, 'subjects'])->name('curriculum.subjects');
    Route::get('/curriculum/chapters', [TeacherCurriculumController::class, 'chapters'])->name('curriculum.chapters');
    Route::get('/curriculum/topics', [TeacherCurriculumController::class, 'topics'])->name('curriculum.topics');
    Route::get('/curriculum/question-counts', [TeacherCurriculumController::class, 'questionCounts'])->name('curriculum.question-counts');

    Route::get('/tickets', [UserTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [UserTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [UserTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/options/subjects', [UserTicketController::class, 'subjects'])->name('tickets.subjects');
    Route::get('/tickets/options/chapters', [UserTicketController::class, 'chapters'])->name('tickets.chapters');
    Route::get('/tickets/{ticket}', [UserTicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [UserTicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    Route::post('/tickets/{ticket}/reply', [UserTicketController::class, 'reply'])->name('tickets.reply');

    Route::get('/notifications', [TeacherNotificationController::class, 'index'])->name('notifications.index');
    Route::match(['get', 'post'], '/notifications/{id}/read', [TeacherNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [TeacherNotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return auth()->check()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('admin.login');
    })->name('index');

    Route::middleware(['auth', 'verified', 'admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/reporting', function () {
            return redirect()->route('admin.dashboard');
        })->name('dashboard.reporting');
        Route::get('/dashboard/materials', [DashboardController::class, 'materials'])->name('dashboard.materials');
        Route::get('/dashboard/syllabus', [SyllabusDashboardController::class, 'index'])->name('dashboard.syllabus');
        Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('change-password');
        Route::get('/prompts', [PromptController::class, 'index'])->name('prompts.index');
        Route::get('/prompts/{prompt}', [PromptController::class, 'edit'])->name('prompts.edit');
        Route::put('/prompts/{prompt}', [PromptController::class, 'update'])->name('prompts.update');
        Route::post('/prompts/{prompt}/reset', [PromptController::class, 'reset'])->name('prompts.reset');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-mail', [SettingController::class, 'sendTestMail'])->name('settings.test-mail');

        Route::get('/students/report', [StudentController::class, 'report'])->name('students.report');
        Route::post('/students/{student}/approve', [StudentController::class, 'approve'])->name('students.approve');
        Route::post('/students/{student}/pending', [StudentController::class, 'pending'])->name('students.pending');
        Route::resource('students', StudentController::class)->except(['create', 'store']);

        Route::get('/teachers/report', [TeacherController::class, 'report'])->name('teachers.report');
        Route::post('/teachers/generate-otp', [TeacherController::class, 'generateOtp'])->name('teachers.generate-otp');
        Route::post('/teachers/{teacher}/approve', [TeacherController::class, 'approve'])->name('teachers.approve');
        Route::post('/teachers/{teacher}/pending', [TeacherController::class, 'pending'])->name('teachers.pending');
        Route::resource('teachers', TeacherController::class)->except(['create', 'store']);

        Route::resource('standards', StandardController::class)->except(['show']);
        Route::get('/standards/{standard}/subjects', [SubjectController::class, 'index'])->name('standards.subjects.index');
        Route::get('/standards/{standard}/subjects/create', [SubjectController::class, 'create'])->name('standards.subjects.create');
        Route::post('/standards/{standard}/subjects', [SubjectController::class, 'store'])->name('standards.subjects.store');
        Route::get('/subjects/{subject}/edit', [SubjectController::class, 'edit'])->name('subjects.edit');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
        Route::get('/subjects/{subject}/chapters', [ChapterController::class, 'index'])->name('subjects.chapters.index');
        Route::get('/subjects/{subject}/chapters/create', [ChapterController::class, 'create'])->name('subjects.chapters.create');
        Route::post('/subjects/{subject}/chapters', [ChapterController::class, 'store'])->name('subjects.chapters.store');
        Route::get('/chapters/{chapter}/edit', [ChapterController::class, 'edit'])->name('chapters.edit');
        Route::put('/chapters/{chapter}', [ChapterController::class, 'update'])->name('chapters.update');
        Route::delete('/chapters/{chapter}', [ChapterController::class, 'destroy'])->name('chapters.destroy');
        Route::get('/chapters/{chapter}/topics', [TopicController::class, 'index'])->name('chapters.topics.index');
        Route::get('/chapters/{chapter}/topics/create', [TopicController::class, 'create'])->name('chapters.topics.create');
        Route::post('/chapters/{chapter}/topics', [TopicController::class, 'store'])->name('chapters.topics.store');
        Route::get('/topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
        Route::put('/topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
        Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');

        Route::get('/upload-data/subjects', [DataUploadController::class, 'subjects'])->name('upload-data.subjects');
        Route::get('/upload-data/chapters', [DataUploadController::class, 'chapters'])->name('upload-data.chapters');
        Route::get('/upload-data', [DataUploadController::class, 'index'])->name('upload-data.index');
        Route::get('/upload-data/create', [DataUploadController::class, 'create'])->name('upload-data.create');
        Route::post('/upload-data', [DataUploadController::class, 'store'])->name('upload-data.store');
        Route::get('/upload-data/{chapterContent}/edit', [DataUploadController::class, 'edit'])->name('upload-data.edit');
        Route::put('/upload-data/{chapterContent}', [DataUploadController::class, 'update'])->name('upload-data.update');
        Route::get('/upload-data/{chapterContent}/questions/{question}/edit', [DataUploadController::class, 'editQuestion'])->name('upload-data.questions.edit');
        Route::put('/upload-data/{chapterContent}/questions/{question}', [DataUploadController::class, 'updateQuestion'])->name('upload-data.questions.update');
        Route::delete('/upload-data/{chapterContent}/questions/{question}', [DataUploadController::class, 'destroyQuestion'])->name('upload-data.questions.destroy');
        Route::get('/upload-data/{chapterContent}/sections/{section}/edit', [DataUploadController::class, 'editSection'])->name('upload-data.sections.edit');
        Route::put('/upload-data/{chapterContent}/sections/{section}', [DataUploadController::class, 'updateSection'])->name('upload-data.sections.update');
        Route::delete('/upload-data/{chapterContent}/sections/{section}', [DataUploadController::class, 'destroySection'])->name('upload-data.sections.destroy');
        Route::get('/upload-data/{chapterContent}', [DataUploadController::class, 'show'])->name('upload-data.show');
        Route::delete('/upload-data/{chapterContent}', [DataUploadController::class, 'destroy'])->name('upload-data.destroy');

        Route::get('/reports/logins', [ReportController::class, 'logins'])->name('reports.logins');
        Route::get('/reports/sessions', [ReportController::class, 'sessions'])->name('reports.sessions');
        Route::get('/email-logs', [EmailLogController::class, 'index'])->name('email-logs.index');
        Route::get('/email-logs/{emailLog}', [EmailLogController::class, 'show'])->name('email-logs.show');
        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
        Route::get('/tickets/{ticket}/attachments/{attachment}', [AdminTicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
        Route::post('/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
        Route::patch('/tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('tickets.status');
    });
});

require __DIR__.'/auth.php';
