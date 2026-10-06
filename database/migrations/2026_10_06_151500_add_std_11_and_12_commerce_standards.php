<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $slugs = [];
        foreach (['11', '12'] as $class) {
            $slugs[$class] = $this->ensureCommerceStandard($class);
        }

        $path = database_path('data/commerce-students-2026-10-06.json');
        if (! is_file($path)) {
            return;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $class = (string) ($row['class'] ?? '');
            $slug = $slugs[$class] ?? null;
            $name = trim(preg_replace('/\s+/u', ' ', (string) ($row['name'] ?? '')) ?? '');
            $mobile = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?: '';
            $medium = strtolower(trim((string) ($row['medium'] ?? '')));
            $email = strtolower(trim((string) ($row['email'] ?? '')));

            if ($slug === null || $name === '' || strlen($mobile) !== 10 || ! in_array($medium, ['english', 'gujarati'], true)) {
                continue;
            }

            $user = User::query()->where('mobile', $mobile)->first();
            if ($user && $user->role !== 'student') {
                continue;
            }

            if ($email === '' || ! str_contains($email, '@')) {
                $email = null;
            } elseif (User::query()->where('email', $email)->when($user, fn ($query) => $query->where('id', '!=', $user->id))->exists()) {
                $email = null;
            }

            if ($user) {
                $user->name = $name;
                $user->medium = $medium;
                $user->standard = $slug;
                $user->is_approved = true;
                if ($email) {
                    $user->email = $email;
                }
                $user->save();

                continue;
            }

            User::query()->create([
                'name' => $name,
                'mobile' => $mobile,
                'email' => $email,
                'medium' => $medium,
                'standard' => $slug,
                'password' => 'Student@123',
                'role' => 'student',
                'is_approved' => true,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('standards')->whereIn('slug', ['std-11-commerce', 'std-12-commerce'])->delete();
    }

    private function ensureCommerceStandard(string $class): string
    {
        $name = "Std {$class} Commerce";
        $slug = "std-{$class}-commerce";

        $existing = DB::table('standards')
            ->where(function ($query) use ($name, $slug) {
                $query->where('slug', $slug)->orWhere('name', $name);
            })
            ->first();

        $science = DB::table('standards')
            ->where(function ($query) use ($class) {
                $query->where('name', 'like', '%'.$class.'%Science%')
                    ->orWhere('name', 'like', '%'.$class.'%science%');
            })
            ->first();
        $sort = (int) ($science->sort_order ?? 0);

        if ($existing) {
            DB::table('standards')->where('id', $existing->id)->update([
                'name' => $name,
                'is_active' => 1,
                'sort_order' => $sort,
                'updated_at' => now(),
            ]);

            return (string) $existing->slug;
        }

        $payload = [
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $sort,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('standards', 'medium')) {
            $payload['medium'] = 'gujarati';
        }

        DB::table('standards')->insert($payload);

        return $slug;
    }
};
