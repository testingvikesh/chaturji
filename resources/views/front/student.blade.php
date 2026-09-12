<x-front-layout title="Student">
    @include('front.partials.page-hero', [
        'badge' => 'For Students',
        'title' => $settings['student_hero_title'],
        'subtitle' => $settings['student_hero_subtitle'],
    ])

    <section class="section-wrap section-py">
        <div class="grid lg:grid-cols-2 gap-10 items-center mb-12">
            <div class="order-2 lg:order-1" data-animate="fade-right">
                <div class="bg-brand-green rounded-2xl p-6 text-white">
                    <h3 class="text-xl font-bold mb-4 text-brand-gold-light">Student Benefits</h3>
                    <ul class="space-y-3">
                        @foreach (['View all homework assignments in one dashboard', 'Submit assignments online before deadlines', 'Get feedback and grades from teachers', 'Track your academic progress over time'] as $benefit)
                            <li class="flex items-start gap-3">
                                <span class="h-6 w-6 shrink-0 rounded-full bg-white/20 flex items-center justify-center text-sm text-brand-gold">✓</span>
                                <span>{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="order-1 lg:order-2" data-animate="fade-left">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4">{{ $settings['student_section_title'] }}</h2>
                <p class="text-slate-600 leading-relaxed">{{ $settings['student_section_description'] }}</p>
            </div>
        </div>

        <div class="section-header" data-animate="fade-up">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Student Features</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-5">
            @foreach (['Assignment List', 'Easy Submission', 'Grades & Feedback'] as $feature)
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-lg hover:border-brand-green-200 transition" data-animate="fade-up" data-delay="{{ $loop->index * 120 }}">
                    <div class="icon-brand-box mb-5">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $feature }}</h3>
                    <p class="text-sm text-slate-600">Manage homework efficiently from your student dashboard.</p>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-10" data-animate="zoom-in">
            <a href="{{ route('student.register') }}" class="btn-brand px-10">Register as Student</a>
        </div>
    </section>
</x-front-layout>
