<?php

namespace Database\Seeders;

use App\Models\Standard;
use Illuminate\Database\Seeder;

class StandardSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Standard::updateOrCreate(
                ['slug' => "standard_{$i}"],
                [
                    'name' => "Standard {$i}",
                    'sort_order' => $i,
                    'is_active' => true,
                ]
            );
        }
    }
}
