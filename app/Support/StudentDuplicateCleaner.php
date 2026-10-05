<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentDuplicateCleaner
{
    public static function normalizeName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);

        return mb_strtolower($name);
    }

    /**
     * @return Collection<string, Collection<int, User>>
     */
    public static function groups(): Collection
    {
        return User::students()
            ->orderBy('id')
            ->get()
            ->groupBy(fn (User $student) => self::normalizeName($student->name).'|'.$student->standard.'|'.$student->medium)
            ->filter(fn (Collection $group) => $group->count() > 1);
    }

    public static function extraCount(): int
    {
        return (int) self::groups()->sum(fn (Collection $group) => $group->count() - 1);
    }

    /**
     * Keep one student per name + standard + medium.
     * Prefer a real email and a real mobile, then the oldest account.
     *
     * @return array{removed: int, names: list<string>}
     */
    public static function remove(): array
    {
        $removed = 0;
        $names = [];

        DB::transaction(function () use (&$removed, &$names) {
            foreach (self::groups() as $group) {
                $ranked = $group->sort(function (User $a, User $b) {
                    $byScore = self::score($b) <=> self::score($a);

                    return $byScore !== 0 ? $byScore : ($a->id <=> $b->id);
                })->values();

                /** @var User $keeper */
                $keeper = $ranked->first();
                $losers = $ranked->slice(1);

                if (! filled($keeper->email)) {
                    $donor = $losers->first(fn (User $student) => filter_var($student->email, FILTER_VALIDATE_EMAIL));
                    if ($donor && ! User::query()->where('email', $donor->email)->where('id', '!=', $keeper->id)->exists()) {
                        $keeper->email = strtolower(trim((string) $donor->email));
                        $keeper->save();
                    }
                }

                foreach ($losers as $loser) {
                    $names[] = $loser->name.' ('.$loser->mobile.')';
                    $loser->delete();
                    $removed++;
                }
            }
        });

        return ['removed' => $removed, 'names' => $names];
    }

    private static function score(User $student): int
    {
        $score = 0;
        if (filter_var($student->email, FILTER_VALIDATE_EMAIL)) {
            $score += 100;
        }

        $mobile = preg_replace('/\D+/', '', (string) $student->mobile) ?? '';
        if (strlen($mobile) >= 10 && ! str_starts_with($mobile, '600000')) {
            $score += 40;
        }
        if ($student->is_approved) {
            $score += 5;
        }

        return $score;
    }
}
