<x-front-layout title="About Us">
    @include('front.partials.page-hero', [
        'badge' => 'About Us',
        'title' => $settings['about_hero_title'],
        'subtitle' => $settings['about_hero_subtitle'],
    ])

    <section class="section-wrap section-py">
        <div class="grid lg:grid-cols-2 gap-10 items-center">
            <div data-animate="fade-right">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4">{{ $settings['about_story_title'] }}</h2>
                <p class="text-slate-600 leading-relaxed mb-3">{{ $settings['about_story_text_1'] }}</p>
                <p class="text-slate-600 leading-relaxed mb-5">{{ $settings['about_story_text_2'] }}</p>
                <div class="flex flex-wrap gap-5">
                    <div>
                        <p class="text-3xl font-bold text-brand-green">{{ $settings['about_years'] }}</p>
                        <p class="text-sm text-slate-500">Years Experience</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-brand-green">{{ $settings['about_happy_students'] }}</p>
                        <p class="text-sm text-slate-500">Happy Students</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-brand-green">{{ $settings['about_expert_teachers'] }}</p>
                        <p class="text-sm text-slate-500">Expert Teachers</p>
                    </div>
                </div>
            </div>
            <div class="bg-brand-green-50 rounded-2xl p-6 lg:p-8 border border-brand-green-100" data-animate="fade-left">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Our Mission & Vision</h3>
                <div class="space-y-5">
                    <div class="flex gap-4">
                        <div class="h-12 w-12 shrink-0 rounded-xl bg-brand-green text-white flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900">Mission</h4>
                            <p class="text-sm text-slate-600 mt-1">{{ $settings['about_mission'] }}</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="h-12 w-12 shrink-0 rounded-xl bg-brand-green-dark text-white flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900">Vision</h4>
                            <p class="text-sm text-slate-600 mt-1">{{ $settings['about_vision'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-brand-green-50 section-py">
        <div class="section-wrap">
            <div class="section-header" data-animate="fade-up">
                <h2 class="text-3xl font-bold text-slate-900">Our Core Values</h2>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach (['Excellence', 'Integrity', 'Innovation', 'Community'] as $title)
                    <div class="bg-white rounded-xl p-5 border border-brand-green-100 text-center shadow-sm" data-animate="fade-up" data-delay="{{ $loop->index * 100 }}">
                        <div class="h-10 w-10 mx-auto rounded-lg bg-brand-green-100 text-brand-green flex items-center justify-center mb-3 font-bold">✓</div>
                        <h3 class="font-bold text-slate-900 mb-2">{{ $title }}</h3>
                        <p class="text-sm text-slate-600">Committed to quality learning and growth.</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-front-layout>
