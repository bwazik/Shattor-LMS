<?php

namespace App\Console\Commands;

use App\Models\WhatsappMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelOctNovFeeMessages extends Command
{
    protected $signature = 'whatsapp:cancel-oct-nov-fees 
                            {--dry-run : Preview what would be cancelled without making changes}
                            {--month= : Specific month to cancel (10 for Oct, 11 for Nov, or "both")}';

    protected $description = 'Cancel queued WhatsApp messages for October and November monthly fees';

    private $stats = [
        'total_checked' => 0,
        'oct_messages' => 0,
        'nov_messages' => 0,
        'deleted' => 0,
        'details' => [],
    ];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $monthOption = $this->option('month') ?: 'both';

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  PRODUCTION MODE - Messages will be permanently deleted!');
            if (!$this->confirm('Are you sure you want to cancel Oct/Nov fee messages?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Determine which months to cancel
        $months = [];
        if ($monthOption === 'both' || $monthOption === '10') {
            $months[] = '(10)';
        }
        if ($monthOption === 'both' || $monthOption === '11') {
            $months[] = '(11)';
        }

        $this->info("Cancelling queued fee messages for months: " . implode(', ', $months));
        $this->line('');

        DB::beginTransaction();

        try {
            // Get all queued fee_paid messages
            $messages = WhatsappMessage::where('template', 'fees_paid')
                ->where('status', 1) // Queued only
                ->get();

            $this->stats['total_checked'] = $messages->count();
            $this->info("Found {$messages->count()} queued 'fees_paid' messages to check");
            $this->line('');

            $progressBar = $this->output->createProgressBar($messages->count());
            $progressBar->start();

            foreach ($messages as $message) {
                $this->processMessage($message, $months, $isDryRun);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line("\n");

            // Display summary
            $this->displaySummary();

            // Show sample details
            if (!empty($this->stats['details'])) {
                $this->displaySampleDetails();
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->info("\n✅ Dry run completed - no changes were made");
            } else {
                if ($this->confirm('Do you want to delete these messages?')) {
                    DB::commit();
                    $this->info("\n✅ Messages deleted successfully!");
                    Log::channel('whatsapp')->info('Oct/Nov fee messages cancelled', $this->stats);
                } else {
                    DB::rollBack();
                    $this->warn("\n❌ Changes rolled back");
                }
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error: " . $e->getMessage());
            Log::channel('whatsapp')->error('Cancel messages command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function processMessage($message, $months, $isDryRun)
    {
        $data = $message->data;
        
        // Check if fee_name exists in data
        if (!isset($data['fee_name'])) {
            return;
        }

        $feeName = $data['fee_name'];
        
        // Check if it's Oct or Nov fee
        $isOct = str_contains($feeName, '(10)');
        $isNov = str_contains($feeName, '(11)');

        if (!$isOct && !$isNov) {
            return; // Not Oct or Nov, skip
        }

        // Check if this month should be cancelled
        $shouldCancel = false;
        if ($isOct && in_array('(10)', $months)) {
            $shouldCancel = true;
            $this->stats['oct_messages']++;
        }
        if ($isNov && in_array('(11)', $months)) {
            $shouldCancel = true;
            $this->stats['nov_messages']++;
        }

        if (!$shouldCancel) {
            return;
        }

        // Store details for reporting
        $this->stats['details'][] = [
            'id' => $message->id,
            'phone' => $message->phone,
            'student_name' => $data['student_name'] ?? 'N/A',
            'fee_name' => $feeName,
            'created_at' => $message->created_at->toDateTimeString(),
        ];

        // Delete the message
        if (!$isDryRun) {
            $message->delete();
        }

        $this->stats['deleted']++;

        Log::channel('whatsapp')->info('Fee message cancelled', [
            'message_id' => $message->id,
            'phone' => $message->phone,
            'fee_name' => $feeName,
            'dry_run' => $isDryRun,
        ]);
    }

    private function displaySummary()
    {
        $this->info("\n📊 Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Messages Checked', $this->stats['total_checked']],
                ['October (10) Messages', $this->stats['oct_messages']],
                ['November (11) Messages', $this->stats['nov_messages']],
                ['---', '---'],
                ['Messages to Delete', $this->stats['deleted']],
            ]
        );
    }

    private function displaySampleDetails()
    {
        $this->line("\n📋 Sample Messages (showing first 20):");
        
        $sample = array_slice($this->stats['details'], 0, 20);
        
        $this->table(
            ['ID', 'Phone', 'Student Name', 'Fee Name', 'Created At'],
            collect($sample)->map(fn($item) => [
                $item['id'],
                $item['phone'],
                mb_substr($item['student_name'], 0, 20),
                mb_substr($item['fee_name'], 0, 35),
                $item['created_at'],
            ])->toArray()
        );

        if (count($this->stats['details']) > 20) {
            $this->info("\n... and " . (count($this->stats['details']) - 20) . " more messages");
        }
    }
}