<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use App\Jobs\SendWhatsappMessage;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendMessage(string $phone, string $template, array $data, bool $isUrgent = false)
    {
        // $planLimitService = new PlanLimitService(auth()->guard('teacher')->user()->id);
        // if (!$planLimitService->hasFeature('whatsapp_messages')) {
        //     return response()->json(['error' => trans('toasts.limitReached')], 422);
        // }

        // Clean phone number
        $phone = $this->formatPhoneNumber($phone);

        // Check for duplicates within 24 hours
        $recentMessage = WhatsappMessage::where('phone', $phone)
            ->where('template', $template)
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subHours(24))
            ->exists();

        if ($recentMessage) {
            Log::channel('whatsapp')->warning("Duplicate message skipped", [
                'phone' => $phone,
                'template' => $template,
            ]);
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
        ]);

        return true;
    }

    public function sendBulkMessages(array $recipients, string $template, callable $dataCallback)
    {
        $batchSize = 100; // Process 100 recipients per batch
        $batches = array_chunk($recipients, $batchSize);

        foreach ($batches as $index => $batch) {
            foreach ($batch as $recipient) {
                $phone = $this->formatPhoneNumber($recipient['student_phone']);
                $data = $dataCallback($recipient);

                // Skip duplicates within 24 hours
                $recentMessage = WhatsappMessage::where('phone', $phone)
                    ->where('template', $template)
                    ->where('status', 'sent')
                    ->where('sent_at', '>=', now()->subHours(24))
                    ->exists();

                if ($recentMessage) {
                    Log::channel('whatsapp')->warning("Duplicate bulk message skipped", [
                        'phone' => $phone,
                        'template' => $template,
                    ]);
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

                // Dispatch with batch delay to spread load
                SendWhatsappMessage::dispatch($message)
                    ->onQueue('default')
                    ->delay(now()->addSeconds($index * 60)); // Delay each batch by 60 seconds
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