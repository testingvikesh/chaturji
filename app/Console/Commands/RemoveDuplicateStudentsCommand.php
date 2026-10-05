<?php

namespace App\Console\Commands;

use App\Support\StudentDuplicateCleaner;
use Illuminate\Console\Command;

class RemoveDuplicateStudentsCommand extends Command
{
    protected $signature = 'students:remove-duplicates';

    protected $description = 'Remove extra student accounts that share the same name, standard, and medium';

    public function handle(): int
    {
        $extra = StudentDuplicateCleaner::extraCount();
        if ($extra === 0) {
            $this->info('No duplicate student names found.');

            return self::SUCCESS;
        }

        $result = StudentDuplicateCleaner::remove();
        $this->info('Removed '.$result['removed'].' duplicate student account(s).');
        foreach ($result['names'] as $name) {
            $this->line('Removed '.$name);
        }

        return self::SUCCESS;
    }
}
