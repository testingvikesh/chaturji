<x-front-layout title="Student Register">
    <x-auth-card
        badge="Student Register"
        title="Create Account"
        subtitle="Fill in your details to register as a student"
        side-title="Start Your Learning Journey"
        side-text="Register once and access homework, assignments, and progress tracking in one place."
        icon="student">

        <form method="POST" action="{{ route('student.register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="auth-label">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="auth-input" placeholder="Enter your full name">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="mobile" class="auth-label">Mobile No.</label>
                    <input id="mobile" type="text" name="mobile" value="{{ old('mobile') }}" required autocomplete="tel" class="auth-input" placeholder="9876543210">
                    <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
                </div>
                <div>
                    <label for="email" class="auth-label">Email <span class="text-slate-400 font-normal">(Optional)</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" class="auth-input" placeholder="email@example.com">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
            </div>

            <div>
                <label class="auth-label">Medium</label>
                <div class="auth-medium-grid">
                    @foreach ($mediums as $value => $label)
                        <label class="auth-medium-option">
                            <input type="radio" name="medium" value="{{ $value }}" @checked(old('medium') === $value) required>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('medium')" class="mt-2" />
            </div>

            <div>
                <label for="standard" class="auth-label">Standard</label>
                <select id="standard" name="standard" required class="auth-select">
                    <option value="">Select your standard</option>
                    @foreach ($standards as $value => $label)
                        <option value="{{ $value }}" @selected(old('standard') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('standard')" class="mt-2" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="auth-label">Password</label>
                    <x-password-input id="password" name="password" required autocomplete="new-password" class="auth-input" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <label for="password_confirmation" class="auth-label">Confirm Password</label>
                    <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="auth-input" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>

            <button type="submit" class="auth-submit mt-2">Create Student Account</button>
        </form>

        <div class="auth-divider"><span>or</span></div>

        <p class="text-center text-sm text-slate-500">
            Already have an account?
            <a href="{{ route('student.login') }}" class="auth-link">Login here</a>
        </p>
        <p class="text-center text-sm text-slate-400 mt-3">
            <a href="{{ route('register') }}" class="hover:text-brand-green transition">&larr; Back to register options</a>
        </p>
    </x-auth-card>
</x-front-layout>
