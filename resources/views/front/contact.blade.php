<x-front-layout title="Contact Us">
    @include('front.partials.page-hero', [
        'badge' => 'Contact Us',
        'title' => $settings['contact_hero_title'],
        'subtitle' => $settings['contact_hero_subtitle'],
    ])

    <section class="section-wrap section-py">
        <div class="grid lg:grid-cols-3 gap-8">
            <div class="space-y-4" data-animate-stagger="100">
                @foreach ([
                    ['title' => 'Our Address', 'value' => $settings['contact_address'], 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['title' => 'Email Us', 'value' => $settings['contact_email'] . "\n" . $settings['contact_support_email'], 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    ['title' => 'Call Us', 'value' => $settings['contact_phone'] . "\n" . $settings['contact_hours'], 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                ] as $item)
                    <div class="bg-white rounded-2xl border border-brand-green-100 p-5 shadow-sm" data-animate-item="fade-right">
                        <div class="h-12 w-12 rounded-xl bg-brand-green-100 text-brand-green flex items-center justify-center mb-4">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                        </div>
                        <h3 class="font-bold text-slate-900 mb-1">{{ $item['title'] }}</h3>
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $item['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="lg:col-span-2" data-animate="fade-left">
                <div class="bg-white rounded-2xl border border-brand-green-100 shadow-sm p-6 lg:p-7">
                    <h2 class="text-xl font-bold text-slate-900 mb-1">Send Us a Message</h2>
                    <p class="text-slate-600 text-sm mb-6">Fill out the form below and we will get back to you within 24 hours.</p>

                    @if (session('success'))
                        <div class="mb-6 rounded-xl bg-brand-green-50 border border-brand-green-200 px-4 py-3 text-sm text-brand-green-dark">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.store') }}" class="space-y-6">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">
                                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">
                                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-medium text-slate-700 mb-1">Subject</label>
                            <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">
                            @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                            <textarea id="message" name="message" rows="5" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-green focus:ring-brand-green text-sm">{{ old('message') }}</textarea>
                            @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="btn-brand px-10">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-front-layout>
