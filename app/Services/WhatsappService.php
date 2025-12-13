<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected $allowMultipleTemplates = [
        'new_device_login',
        'login_notification',
        'security_code_updated',
        'multiple_account_attempt',
        'import_main_report',
        'offline_quiz_notification',
    ];

    public function sendMessage(string $phone, string $template, array $data, bool $isUrgent = false)
    {
        // Clean phone number
        $phone = $this->formatPhoneNumber($phone);

        // Skip cache lock and duplicate check for specific templates
        $allowMultiple = in_array($template, $this->allowMultipleTemplates);
        $lockKey = "whatsapp_lock_{$phone}_{$template}";
        $lockAcquired = $allowMultiple || Cache::lock($lockKey, 300)->get();

        if ($lockAcquired) {
            // Check for duplicates within 24 hours for non-allowed templates
            if (!$allowMultiple) {
                $recentMessage = WhatsappMessage::where('phone', $phone)
                    ->where('template', $template)
                    ->whereIn('status', [1, 2])
                    ->where('created_at', '>=', now()->subHours(5))
                    ->exists();

                if ($recentMessage) {
                    Log::channel('whatsapp')->warning("Duplicate message skipped", [
                        'phone' => $phone,
                        'template' => $template,
                    ]);
                    Cache::lock($lockKey)->release();
                    return false;
                }
            }

            // Add is_urgent to data
            $data['is_urgent'] = $isUrgent;

            // Create message record
            $message = WhatsappMessage::create([
                'phone' => $phone,
                'template' => $template,
                'data' => $data,
                'status' => 1,
                'attempts' => 0,
            ]);

            // Calculate delay with proper sequencing
            $delayGap = $isUrgent ? random_int(30, 60) : random_int(150, 300);

            // SEPARATE queues for urgent vs non-urgent
            $lastScheduledKey = $isUrgent
                ? 'whatsapp_last_urgent_time'
                : 'whatsapp_last_normal_time';

            $lockSchedule = Cache::lock("whatsapp_schedule_lock_{$isUrgent}", 10);

            if ($lockSchedule->get()) {
                $lastScheduledTime = Cache::get($lastScheduledKey, now()->timestamp);
                $now = now()->timestamp;

                // If last scheduled time is in the past, start from now
                $baseTime = max($lastScheduledTime, $now);

                // Add the delay gap
                $scheduledTime = $baseTime + $delayGap;

                // Store the new scheduled time
                Cache::put($lastScheduledKey, $scheduledTime, 3600);
                $lockSchedule->release();

                // Calculate actual delay from now
                $delaySeconds = $scheduledTime - $now;
            } else {
                // Fallback: simple random delay
                $delaySeconds = $delayGap;
            }

            // Dispatch job to appropriate queue with delay
            $queue = $isUrgent ? 'urgent' : 'default';
            $delay = now()->addSeconds($delaySeconds);
            SendWhatsappMessage::dispatch($message)->onQueue($queue)->delay($delay);

            Log::channel('whatsapp')->info('WhatsApp message queued', [
                'message_id' => $message->id,
                'phone' => $phone,
                'template' => $template,
                'queue' => $queue,
                'delay_seconds' => $delaySeconds,
            ]);

            if (!$allowMultiple) {
                Cache::lock($lockKey)->release();
            }
            return true;
        }

        Log::channel('whatsapp')->warning("Message blocked by cache lock", [
            'phone' => $phone,
            'template' => $template,
        ]);
        return false;
    }

    public function sendBulkMessages(array $recipients, string $template, callable $dataCallback)
    {
        $batchSize = 100; // Process 100 recipients per batch
        $batches = array_chunk($recipients, $batchSize);
        $baseDelay = 0; // Cumulative delay for sequential processing

        foreach ($batches as $index => $batch) {
            foreach ($batch as $recipientIndex => $recipient) {
                $phone = $this->formatPhoneNumber($recipient['student_phone']);
                $data = $dataCallback($recipient);

                // Cache lock for bulk messages (5-minute lock)
                $lockKey = "whatsapp_lock_{$phone}_{$template}";
                if (Cache::lock($lockKey, 300)->get()) {
                    // Check for duplicates within 24 hours
                    $recentMessage = WhatsappMessage::where('phone', $phone)
                        ->where('template', $template)
                        ->whereIn('status', [1, 2])
                        ->where('created_at', '>=', now()->subHours(24)) // Use created_at
                        ->exists();

                    if ($recentMessage) {
                        Log::channel('whatsapp')->warning("Duplicate bulk message skipped", [
                            'phone' => $phone,
                            'template' => $template,
                        ]);
                        Cache::lock($lockKey)->release();
                        continue;
                    }

                    // Add is_urgent to data
                    $data['is_urgent'] = false;

                    $message = WhatsappMessage::create([
                        'phone' => $phone,
                        'template' => $template,
                        'data' => $data,
                        'status' => 1,
                        'attempts' => 0,
                    ]);

                    // Stagger delays: 180–220s per message
                    $delayedSeconds = random_int(150, 300);
                    $delay = now()->addSeconds($baseDelay + $delayedSeconds);

                    // Dispatch with staggered delay
                    SendWhatsappMessage::dispatch($message)
                        ->onQueue('default')
                        ->delay($delay);

                    Log::channel('whatsapp')->info('WhatsApp message queued', [
                        'message_id' => $message->id,
                        'phone' => $phone,
                        'template' => $template,
                        'queue' => 'default',
                        'delay_seconds' => $baseDelay + $delayedSeconds,
                    ]);

                    Cache::lock($lockKey)->release();

                    // Increment base delay for next message
                    $baseDelay += $delayedSeconds;
                } else {
                    Log::channel('whatsapp')->warning("Bulk message blocked by cache lock", [
                        'phone' => $phone,
                        'template' => $template,
                    ]);
                }
            }
            // Add batch delay (optional, kept for large batches)
            $baseDelay += 60;
        }

        Log::channel('whatsapp')->info('WhatsApp bulk messages queued', [
            'template' => $template,
            'recipient_count' => count($recipients),
        ]);

        return true;
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);

        if (preg_match('/^\+20[0-9]{10}$/', $phone)) {
            return $phone;
        }

        $phone = preg_replace('/^\+?20/', '', $phone);

        $phone = ltrim($phone, '0');

        if (strlen($phone) !== 10) {
            return $phone;
        }

        return '+20' . $phone;
    }
}