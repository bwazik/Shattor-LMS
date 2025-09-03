<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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

            // Dispatch job to appropriate queue
            $queue = $isUrgent ? 'urgent' : 'default';
            SendWhatsappMessage::dispatch($message)->onQueue($queue);

            Log::channel('whatsapp')->info('WhatsApp message queued', [
                'message_id' => $message->id,
                'phone' => $phone,
                'template' => $template,
                'queue' => $queue,
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
        $batchSize = 100;
        $batches = array_chunk($recipients, $batchSize);

        foreach ($batches as $index => $batch) {
            foreach ($batch as $recipient) {
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

                    // Dispatch with batch delay
                    SendWhatsappMessage::dispatch($message)
                        ->onQueue('default')
                        ->delay(now()->addSeconds($index * 60));

                    Log::channel('whatsapp')->info('WhatsApp message queued', [
                        'message_id' => $message->id,
                        'phone' => $phone,
                        'template' => $template,
                        'queue' => 'default',
                    ]);

                    Cache::lock($lockKey)->release();
                } else {
                    Log::channel('whatsapp')->warning("Bulk message blocked by cache lock", [
                        'phone' => $phone,
                        'template' => $template,
                    ]);
                }
            }
        }

        Log::channel('whatsapp')->info('WhatsApp bulk messages queued', [
            'template' => $template,
            'recipient_count' => count($recipients),
        ]);

        return true;
    }

    protected function formatPhoneNumber(string $phone): string
    {
        // Ensure phone number has country code (+20 for Egypt) and is max 20 characters
        $phone = '0' . ltrim($phone, '0');
        if (!str_starts_with($phone, '+20')) {
            $phone = '+20' . ltrim($phone, '0');
        }
        return substr($phone, 0, 20);
    }
}