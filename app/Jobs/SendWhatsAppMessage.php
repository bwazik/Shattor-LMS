<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;

    public $tries = 3; // Retry up to 3 times
    public $backoff = [30, 60, 120]; // Delay retries by 30s, 60s, 120s

    public function __construct(WhatsappMessage $message)
    {
        $this->message = $message;
        $this->queue = 'default';
    }

    public function handle()
    {
        $apiUrl = env('WHATSAPP_API_URL', 'https://noti-fire.com/api/send/message');
        $deviceId = env('WHATSAPP_DEVICE_ID', '');

        if (!$apiUrl || !$deviceId) {
            $this->message->update([
                'status' => 3,
                'error_message' => 'WhatsApp API configuration missing',
                'attempts' => $this->message->attempts + 1,
            ]);
            Log::channel('whatsapp')->error('WhatsApp API configuration missing', ['message_id' => $this->message->id]);
            return;
        }

        // Format message based on template
        $content = $this->formatMessage($this->message->template, $this->message->data);

        try {
            // Send message via Noti Fire API
            $response = Http::post($apiUrl, [
                'device_id' => $deviceId,
                'to' => $this->message->phone,
                'message' => $content,
            ]);

            if ($response->successful()) {
                $this->message->update([
                    'status' => 2,
                    'sent_at' => now(),
                    'attempts' => $this->message->attempts + 1,
                ]);
                Log::channel('whatsapp')->info('WhatsApp message sent', [
                    'message_id' => $this->message->id,
                    'phone' => $this->message->phone,
                    'template' => $this->message->template,
                ]);
            } else {
                $error = $response->json('error', $response->body());
                $this->message->update([
                    'status' => 3,
                    'error_message' => $error,
                    'attempts' => $this->message->attempts + 1,
                ]);
                Log::channel('whatsapp')->error('WhatsApp message failed', [
                    'message_id' => $this->message->id,
                    'phone' => $this->message->phone,
                    'response' => $error,
                ]);
                $this->fail();
            }
        } catch (\Exception $e) {
            $this->message->update([
                'status' => 3,
                'error_message' => $e->getMessage(),
                'attempts' => $this->message->attempts + 1,
            ]);
            Log::channel('whatsapp')->error('WhatsApp message exception', [
                'message_id' => $this->message->id,
                'phone' => $this->message->phone,
                'error' => $e->getMessage(),
            ]);
            $this->fail();
        }

        // Enforce 8–12 second delay to mimic human behavior
        sleep(random_int(8, 12));
    }

    protected function formatMessage($template, $data)
    {
        switch ($template) {
            case 'new_device_login':
                return "{$data['name']}, عامل ايه! 👋\n\nفي جهاز جديد دخل على حسابك في منصة شطُّور يوم "
                    . "{$data['date']} الساعة {$data['time']}.";
            case 'student_credentials':
                $studentName = $data['student_name'];
                $username = $data['username'];
                $password = $data['password'];
                $loginUrl = $data['login_url'];
                $settingsUrl = $data['settings_url'];
                $teacherName = $data['teacher_name'] ?? null;

                $teacherLine = $teacherName ? "المدرس: {$teacherName}\n" : "";

                return "{$studentName}، أهلاً بيك 👋\n"
                    . "حسابك علي منصة شطّور جاهز دلوقتي!\n\n"
                    . "بيانات الدخول:\n\n"
                    . "اليوزرنيم: {$username}\n"
                    . "الباسوورد: {$password}\n"
                    . $teacherLine . "\n"
                    . "تسجيل الدخول:\n{$loginUrl}\n"
                    . "تقدر تغيّر اليوزرنيم أو الباسوورد من الإعدادات:\n{$settingsUrl}\n\n"
                    . "ممكن تقولنا المجموعة بتعتك عشان نربط حسابك بيها؟ 🤔";
            default:
                return "إشعار: {$data['message']}";
        }
    }
}