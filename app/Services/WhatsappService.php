<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendMessage(string $phone, string $template, array $data, bool $isUrgent = false)
    {
        // Clean phone number
        $phone = $this->formatPhoneNumber($phone);

        // Cache lock to prevent rapid triggers (5-minute lock)
        $lockKey = "whatsapp_lock_{$phone}_{$template}";
        if (Cache::lock($lockKey, 300)->get()) {
            // Check for duplicates within 24 hours
            $recentMessage = WhatsappMessage::where('phone', $phone)
                ->where('template', $template)
                ->whereIn('status', [1, 2])
                ->where('created_at', '>=', now()->subHours(24)) // Use created_at
                ->exists();

            if ($recentMessage) {
                Log::channel('whatsapp')->warning("Duplicate message skipped", [
                    'phone' => $phone,
                    'template' => $template,
                ]);
                Cache::lock($lockKey)->release();
                return false;
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

            // Global delay counter for 40–60s gap between sends
            $globalLockKey = 'whatsapp_send_message_delay';
            $baseDelay = Cache::get($globalLockKey, 0);
            $delaySeconds = $baseDelay + random_int(40, 60);
            Cache::put($globalLockKey, $delaySeconds, 3600);

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

            Cache::lock($lockKey)->release();
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
                    $delay = now()->addSeconds($baseDelay + random_int(180, 220));

                    // Dispatch with staggered delay
                    SendWhatsappMessage::dispatch($message)
                        ->onQueue('default')
                        ->delay($delay);

                    Log::channel('whatsapp')->info('WhatsApp message queued', [
                        'message_id' => $message->id,
                        'phone' => $phone,
                        'template' => $template,
                        'queue' => 'default',
                        'delay_seconds' => $baseDelay + random_int(180, 220),
                    ]);

                    Cache::lock($lockKey)->release();

                    // Increment base delay for next message
                    $baseDelay += random_int(180, 220);
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
        $phone = '0' . ltrim($phone, '0');
        if (!str_starts_with($phone, '+20')) {
            $phone = '+20' . ltrim($phone, '0');
        }
        return substr($phone, 0, 20);
    }
}