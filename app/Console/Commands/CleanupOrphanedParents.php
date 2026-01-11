<?php

namespace App\Console\Commands;

use App\Models\MyParent;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOrphanedParents extends Command
{
    protected $signature = 'parents:cleanup-orphaned 
                            {--dry-run : Preview what would be deleted without making changes}
                            {--force-delete : Force delete parents with no students instead of soft-delete}';

    protected $description = 'Soft-delete parents whose all children are soft-deleted, or force-delete parents with no students';

    private $stats = [
        'total_parents_checked' => 0,
        'parents_with_active_students' => 0,
        'parents_with_all_deleted_students' => 0,
        'parents_with_no_students' => 0,
        'parents_soft_deleted' => 0,
        'parents_force_deleted' => 0,
        'soft_delete_details' => [],
        'force_delete_details' => [],
    ];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $forceDelete = $this->option('force-delete');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  PRODUCTION MODE - Parents will be deleted!');
            if ($forceDelete) {
                $this->error('🔥 FORCE DELETE MODE - Parents with no students will be PERMANENTLY deleted!');
            }
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info("Checking parents...");
        if ($forceDelete) {
            $this->warn("Force delete enabled: Parents with NO students will be permanently deleted");
        }
        $this->line('');

        DB::beginTransaction();

        try {
            $parents = MyParent::whereNull('deleted_at')
                ->with(['students' => function($query) {
                    $query->withTrashed();
                }])
                ->get();

            $this->stats['total_parents_checked'] = $parents->count();
            
            if ($parents->isEmpty()) {
                $this->warn("No active parents found.");
                DB::rollBack();
                return 0;
            }

            $this->info("Found {$parents->count()} active parents to check");
            $this->line('');

            $progressBar = $this->output->createProgressBar($parents->count());
            $progressBar->start();

            foreach ($parents as $parent) {
                $this->processParent($parent, $isDryRun, $forceDelete);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line("\n");

            $this->displaySummary();

            if (!empty($this->stats['soft_delete_details']) || !empty($this->stats['force_delete_details'])) {
                $this->displayDetailedReport();
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->info("\n✅ Dry run completed - no changes were made");
            } else {
                if ($this->confirm('Do you want to delete these parents?')) {
                    DB::commit();
                    $this->info("\n✅ Parents deleted successfully!");
                    Log::channel('excel-import')->info('Orphaned parents cleanup completed', $this->stats);
                } else {
                    DB::rollBack();
                    $this->warn("\n❌ Changes rolled back");
                }
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error: " . $e->getMessage());
            Log::channel('excel-import')->error('Cleanup orphaned parents failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function processParent($parent, $isDryRun, $forceDelete)
    {
        $allStudents = $parent->students;
        
        // Case 1: NO students at all
        if ($allStudents->isEmpty()) {
            $this->stats['parents_with_no_students']++;
            $this->stats['force_delete_details'][] = [
                'parent_id' => $parent->id,
                'parent_name' => $parent->getTranslation('name', 'ar'),
                'parent_phone' => $parent->phone,
                'reason' => 'No students',
            ];

            if (!$isDryRun) {
                if ($forceDelete) {
                    $parent->forceDelete();
                    $this->stats['parents_force_deleted']++;
                } else {
                    $parent->delete();
                    $this->stats['parents_soft_deleted']++;
                }
            } else {
                $forceDelete ? $this->stats['parents_force_deleted']++ : $this->stats['parents_soft_deleted']++;
            }
            return;
        }

        $activeStudents = $allStudents->filter(fn($s) => $s->deleted_at === null);
        $deletedStudents = $allStudents->filter(fn($s) => $s->deleted_at !== null);

        // Case 2: Has active students
        if ($activeStudents->count() > 0) {
            $this->stats['parents_with_active_students']++;
            return;
        }

        // Case 3: All students soft-deleted
        $this->stats['parents_with_all_deleted_students']++;
        $this->stats['soft_delete_details'][] = [
            'parent_id' => $parent->id,
            'parent_name' => $parent->getTranslation('name', 'ar'),
            'parent_phone' => $parent->phone,
            'total_students' => $allStudents->count(),
            'student_names' => $deletedStudents->pluck('name')->map(fn($n) => is_array($n) ? ($n['ar'] ?? 'N/A') : $n)->implode(', '),
        ];

        if (!$isDryRun) {
            $parent->delete();
        }
        $this->stats['parents_soft_deleted']++;
    }

    private function displaySummary()
    {
        $this->info("\n📊 Summary:");
        $this->table(['Metric', 'Count'], [
            ['Total Parents Checked', $this->stats['total_parents_checked']],
            ['Parents with Active Students', $this->stats['parents_with_active_students']],
            ['Parents with All Deleted Students', $this->stats['parents_with_all_deleted_students']],
            ['Parents with NO Students', $this->stats['parents_with_no_students']],
            ['---', '---'],
            ['Parents Soft-Deleted', $this->stats['parents_soft_deleted']],
            ['Parents Force-Deleted', $this->stats['parents_force_deleted']],
        ]);
    }

    private function displayDetailedReport()
    {
        if (!empty($this->stats['soft_delete_details'])) {
            $this->line("\n📋 Soft-Delete (all students deleted):");
            $details = array_slice($this->stats['soft_delete_details'], 0, 15);
            $this->table(['ID', 'Name', 'Phone', 'Students', 'Names'], 
                collect($details)->map(fn($i) => [
                    $i['parent_id'], 
                    mb_substr($i['parent_name'], 0, 20), 
                    $i['parent_phone'], 
                    $i['total_students'],
                    mb_substr($i['student_names'], 0, 30)
                ])->toArray()
            );
            if (count($this->stats['soft_delete_details']) > 15) {
                $this->info("... +" . (count($this->stats['soft_delete_details']) - 15) . " more");
            }
        }

        if (!empty($this->stats['force_delete_details'])) {
            $this->line("\n🔥 Force-Delete (no students):");
            $details = array_slice($this->stats['force_delete_details'], 0, 15);
            $this->table(['ID', 'Name', 'Phone', 'Reason'], 
                collect($details)->map(fn($i) => [
                    $i['parent_id'], 
                    mb_substr($i['parent_name'], 0, 20), 
                    $i['parent_phone'], 
                    $i['reason']
                ])->toArray()
            );
            if (count($this->stats['force_delete_details']) > 15) {
                $this->info("... +" . (count($this->stats['force_delete_details']) - 15) . " more");
            }
        }
    }
}