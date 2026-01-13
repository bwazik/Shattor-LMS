<?php

namespace App\Console\Commands;

use App\Models\WhatsappMessage;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RequeueFailedWhatsappMessages extends Command
{
    protected $signature = 'whatsapp:requeue-failed {--from=} {--to=} {--all}';
    protected $description = 'Requeue failed WhatsApp messages';

    protected $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
    }

    public function handle()
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $all = $this->option('all');

        // Build query
        $query = WhatsappMessage::where('status', 3);

        if ($all) {
            $this->info("Requeuing ALL failed messages...");
        } elseif ($from && $to) {
            $query->whereBetween('id', [$from, $to]);
            $this->info("Requeuing failed messages from ID {$from} to {$to}...");
        } else {
            $this->error('Please specify --from and --to options, or use --all');
            return 1;
        }

        $failedMessages = $query->orderBy('id')->get();

        if ($failedMessages->isEmpty()) {
            $this->warn('No failed messages found.');
            return 0;
        }

        $this->info("Found {$failedMessages->count()} failed messages.");
        $this->table(
            ['ID', 'Phone', 'Template', 'Error'],
            $failedMessages->take(10)->map(fn($m) => [
                $m->id,
                $m->phone,
                $m->template,
                substr($m->error_message, 0, 50) . '...'
            ])
        );

        if ($failedMessages->count() > 10) {
            $this->info("... and " . ($failedMessages->count() - 10) . " more.");
        }

        if (!$this->confirm('Do you want to requeue these messages?', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        $bar = $this->output->createProgressBar($failedMessages->count());
        $bar->start();

        $requeued = 0;
        $skipped = 0;

        foreach ($failedMessages as $message) {
            try {
                // Delete the failed message record first
                $phone = $message->phone;
                $template = $message->template;
                $data = $message->data;
                $isUrgent = $data['is_urgent'] ?? false;
                
                // Delete old failed message
                $message->delete();

                // Use the service to send again (this creates a new message record)
                $this->whatsappService->sendMessage($phone, $template, $data, true);

                $requeued++;
                
            } catch (\Exception $e) {
                $this->error("\nFailed to requeue message ID {$message->id}: {$e->getMessage()}");
                Log::channel('whatsapp')->error('Failed to requeue message', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Successfully requeued {$requeued} messages!");
        
        if ($skipped > 0) {
            $this->warn("⚠️  Skipped {$skipped} messages due to errors.");
        }

        return 0;
    }
}