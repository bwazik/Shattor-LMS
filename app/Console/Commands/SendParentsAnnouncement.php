<?php

namespace App\Console\Commands;

use App\Models\MyParent;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendParentsAnnouncement extends Command
{
    protected $signature = 'whatsapp:send-parents-announcement 
                            {--dry-run : Preview who would receive the message}
                            {--teacher= : Send only to parents of students under specific teacher}
                            {--limit= : Limit number of messages to send (for testing)}';

    protected $description = 'Send announcement message to all parents about the new parent portal';

    protected $whatsappService;

    private $stats = [
        'total_parents' => 0,
        'messages_sent' => 0,
        'skipped_no_phone' => 0,
        'skipped_no_active_students' => 0,
        'sample_recipients' => [],
    ];

    public function __construct(WhatsappService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
    }

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $teacherId = $this->option('teacher');
        $limit = $this->option('limit');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No messages will be sent');
        } else {
            $this->warn('⚠️  PRODUCTION MODE - Messages will be sent to parents!');
            if (!$this->confirm('Are you sure you want to send the announcement to all parents?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info("Preparing to send announcement message...");
        $this->line('');

        // Get parents query
        $parentsQuery = MyParent::whereNull('deleted_at')
            ->with(['students' => function($query) use ($teacherId) {
                $query->whereNull('deleted_at'); // Only active students
                if ($teacherId) {
                    $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId));
                }
            }]);

        if ($limit) {
            $parentsQuery->limit($limit);
        }

        $parents = $parentsQuery->get();

        $this->stats['total_parents'] = $parents->count();

        if ($parents->isEmpty()) {
            $this->warn("No parents found.");
            return 0;
        }

        $this->info("Found {$parents->count()} parents");
        $this->line('');

        $progressBar = $this->output->createProgressBar($parents->count());
        $progressBar->start();

        foreach ($parents as $parent) {
            $this->processParent($parent, $isDryRun);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line("\n");

        // Display summary
        $this->displaySummary($isDryRun);

        if ($isDryRun) {
            $this->info("\n✅ Dry run completed - no messages were sent");
        } else {
            $this->info("\n✅ Messages queued successfully!");
            $this->warn("Messages will be sent over the next few hours with delays between them");
        }

        Log::channel('whatsapp')->info('Parents announcement sent', $this->stats);

        return 0;
    }

    private function processParent($parent, $isDryRun)
    {
        // Skip if no phone
        if (empty($parent->phone)) {
            $this->stats['skipped_no_phone']++;
            return;
        }

        // Skip if no active students
        if ($parent->students->isEmpty()) {
            $this->stats['skipped_no_active_students']++;
            return;
        }

        // Store sample for display
        if (count($this->stats['sample_recipients']) < 10) {
            $this->stats['sample_recipients'][] = [
                'parent_name' => $parent->getTranslation('name', 'ar'),
                'phone' => $parent->phone,
                'students_count' => $parent->students->count(),
            ];
        }

        if (!$isDryRun) {
            // Send the message
            $this->whatsappService->sendMessage(
                $parent->phone,
                'parent_portal_announcement',
                [
                    'parent_name' => $parent->getTranslation('name', 'ar'),
                ],
                false // Not urgent
            );
        }

        $this->stats['messages_sent']++;
    }

    private function displaySummary($isDryRun)
    {
        $this->info("\n📊 Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Parents Checked', $this->stats['total_parents']],
                ['Messages ' . ($isDryRun ? 'Would Be' : '') . ' Sent', $this->stats['messages_sent']],
                ['Skipped (No Phone)', $this->stats['skipped_no_phone']],
                ['Skipped (No Active Students)', $this->stats['skipped_no_active_students']],
            ]
        );

        if (!empty($this->stats['sample_recipients'])) {
            $this->line("\n📋 Sample Recipients (first 10):");
            $this->table(
                ['Parent Name', 'Phone', 'Active Students'],
                collect($this->stats['sample_recipients'])->map(fn($item) => [
                    mb_substr($item['parent_name'], 0, 25),
                    $item['phone'],
                    $item['students_count'],
                ])->toArray()
            );

            if ($this->stats['messages_sent'] > 10) {
                $this->info("... and " . ($this->stats['messages_sent'] - 10) . " more recipients");
            }
        }

        $this->line("\n💡 Message Preview:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line($this->getMessageContent());
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }

    private function getMessageContent()
    {
        return "📢 تنبيه مهم لأولياء الأمور

حبّينا نبلّغ حضراتكم إنه هيتم إيقاف رسايل الحضور والغياب والمصاريف اللي كانت بتتبعت قبل كده.

🔗 وبدل ده
وفّرنا لينك متابعة للطالب تقدر تدخل عليه في أي وقت وتشوف:

• حضور وغياب ابنك (مُحدّث أول بأول)
• الدرجات والتقييمات
• المصاريف وحالة السداد
• بيانات الطالب كاملة

🔐 الدخول بيكون سهل وبسيط
هتدخل باستخدام:
• رقم الطالب
• ورقم موبايل ولي الأمر المسجّل عندنا

📱 اللينك شغال في أي وقت ومن أي موبايل أو كمبيوتر، ومش محتاج تستنى رسايل تاني.

📞 للاستفسار أو أي مساعدة
تقدروا تتواصلوا مع السنتر على الرقم:
01285527877

هدفنا دايمًا نسهّل المتابعة ونقدّم خدمة أحسن لولادنا ❤️";
    }
}