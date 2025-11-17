<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStudentTeacherTimestamps extends Command
{
    protected $signature = 'backfill:student-teacher-timestamps
                            {--dry-run : Preview changes without saving}
                            {--batch-size=100 : Number of records to process at once}';

    protected $description = 'Backfill created_at and updated_at for student_teacher records';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  PRODUCTION MODE - Changes will be permanent!');
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Checking student_teacher records without timestamps...');
        $this->line('');

        // Count records that need updating
        $totalRecords = DB::table('student_teacher')
            ->whereNull('created_at')
            ->count();

        if ($totalRecords === 0) {
            $this->info('✅ All records already have timestamps!');
            return 0;
        }

        $this->info("Found {$totalRecords} records that need timestamps");
        $this->line('');

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        try {
            $updated = 0;
            $errors = 0;
            $progressBar = $this->output->createProgressBar($totalRecords);
            $progressBar->start();

            // Process in batches
            DB::table('student_teacher')
                ->whereNull('created_at')
                ->orderBy('id')
                ->chunk($batchSize, function ($records) use (&$updated, &$errors, $progressBar, $isDryRun) {
                    foreach ($records as $record) {
                        try {
                            // Get student's created_at as fallback
                            $student = DB::table('students')
                                ->where('id', $record->student_id)
                                ->first();

                            if (!$student) {
                                $this->warn("\nStudent ID {$record->student_id} not found, skipping...");
                                $errors++;
                                $progressBar->advance();
                                continue;
                            }

                            // Use student's created_at as the timestamp
                            $timestamp = $student->created_at ?? now();

                            if (!$isDryRun) {
                                DB::table('student_teacher')
                                    ->where('id', $record->id)
                                    ->update([
                                        'created_at' => $timestamp,
                                        'updated_at' => $timestamp,
                                    ]);
                            }

                            $updated++;
                            $progressBar->advance();

                        } catch (\Exception $e) {
                            $this->error("\nError updating record ID {$record->id}: {$e->getMessage()}");
                            $errors++;
                            $progressBar->advance();
                        }
                    }
                });

            $progressBar->finish();
            $this->line("\n");

            // Display summary
            $this->info('═══════════════════════════════════════');
            $this->info('📊 Summary:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Records Checked', $totalRecords],
                    ['Records Updated', $updated],
                    ['Errors', $errors],
                ]
            );
            $this->info('═══════════════════════════════════════');

            if ($isDryRun) {
                $this->warn("\n🔍 This was a dry run - no actual changes were made");
                return 0;
            }

            if ($this->confirm('Do you want to commit these changes?')) {
                DB::commit();
                $this->info("\n✅ Changes committed successfully!");
            } else {
                DB::rollBack();
                $this->warn("\n❌ Changes rolled back");
            }

            return 0;

        } catch (\Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $this->error("\n❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}