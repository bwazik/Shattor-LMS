<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillStudentTeacherDates extends Command
{
    protected $signature = 'student-teacher:fill-dates {--dry-run : Run without applying changes}';

    protected $description = 'Fill student_teacher.created_at dates';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn("Running in DRY RUN mode. No data will be changed.\n");
        }

        $this->info("Scanning student_teacher records...");

        $records = DB::table('student_teacher')->get();
        $updatedCount = 0;

        foreach ($records as $record) {

            $firstInvoice = DB::table('invoices')
                ->where('student_id', $record->student_id)
                ->where('type', 2)
                ->orderBy('created_at', 'asc')
                ->value('created_at');

            if ($firstInvoice) {

                if ($dryRun) {
                    $this->line("Would update student_teacher ID {$record->id} → {$firstInvoice}");
                } else {
                    DB::table('student_teacher')
                        ->where('id', $record->id)
                        ->update([
                            'created_at' => $firstInvoice,
                            'updated_at' => $firstInvoice,
                        ]);
                }

                $updatedCount++;
            }
        }

        if ($dryRun) {
            $this->warn("\nDRY RUN complete. {$updatedCount} rows WOULD have been updated.");
        } else {
            $this->info("\nDone! {$updatedCount} rows updated.");
        }
    }
}
