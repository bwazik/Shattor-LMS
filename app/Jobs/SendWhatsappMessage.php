<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use App\Services\GeminiService;
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
            Log::channel('whatsapp')->error('WhatsApp API configuration missing', [
                'message_id' => $this->message->id,
                'queue' => $this->queue,
            ]);
            $this->fail();
            return;
        }

        // Format message based on template
        $content = $this->formatMessage($this->message->template, $this->message->data);

        try {
            // Send message via Noti Fire API
            $response = Http::timeout(60)->post($apiUrl, [
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
                    'queue' => $this->queue,
                ]);
                $this->delete(); // Explicitly delete job from queue
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
                    'queue' => $this->queue,
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
                'queue' => $this->queue,
            ]);
            $this->fail();
        }
    }

    protected function formatMessage($template, $data)
    {
        switch ($template) {
            case 'new_device_login':
                return "{$data['name']}, عامل/ه ايه! 👋🏻\n\nفي جهاز جديد دخل على حسابك في منصة شطُّور يوم "
                    . "{$data['date']} الساعة {$data['time']}.";
            case 'student_credentials':
                $studentName = $data['student_name'];
                $username = $data['username'];
                $password = $data['password'];
                $loginUrl = $data['login_url'];
                $settingsUrl = $data['settings_url'];
                $teacherName = $data['teacher_name'] ?? null;

                $teacherLine = $teacherName ? "المدرس: {$teacherName}\n" : "";

                return "{$studentName}، أهلاً بيك/ي 👋🏻\n"
                    . "حسابك علي منصة شطّور جاهز دلوقتي!\n\n"
                    . "بيانات الدخول:\n\n"
                    . "اليوزرنيم: {$username}\n"
                    . "الباسوورد: {$password}\n"
                    . $teacherLine . "\n\n"
                    . "ملحوظة: لو حصل معاك مشكلة في تسجيل الدخول جرب اليوزرنيم لوحده كوبي والباسوورد لوحده كوبي من غير أي مسافات\n\n"
                    . "تسجيل الدخول:\n{$loginUrl}\n"
                    . "تقدر تغيّر اليوزرنيم أو الباسوورد من الإعدادات:\n{$settingsUrl}\n\n"
                    . "ممكن تقولنا المجموعة بتاعتك عشان نربط حسابك بيها؟ 🤔";
            case 'password_updated':
                return "تم تغيير الباسوورد الخاص بحسابك على منصة شطّور \nلو ما كنتش إنت اللي عملت التغيير ده، ابعتلنا دلوقتي!";
            case 'compensatory_accepted':
                return "ازيك يا {$data['student_name']} 👋🏻\n"
                    . "طلب التعويض بتاعك اتقبل من {$data['teacher_name']} 🫣\n\n"
                    . "الحصة: {$data['lesson_title']}";
            case 'compensatory_rejected':
                return "ازيك يا {$data['student_name']} 👋🏻\n"
                    . "للأسف طلب التعويض بتاعك اترفض من {$data['teacher_name']} 😔\n\n"
                    . "الحصة: {$data['lesson_title']}";
            case 'fees_paid':
                return "السلام عليكم 👋🏻\n"
                    . "نحب نبلغ حضرتك إن الطالب {$data['student_name']} دفع {$data['fee_name']} بمبلغ {$data['paid_amount']} يوم {$data['date']} الساعة {$data['time']} عند {$data['teacher_name']} ✅\n\n"
                    . "برجاء ملاحظة إن تاريخ الدفع المدوَّن في النظام ممكن مايبقاش مطابق باليوم اللي حضرتك سددت فيه فعليًا، لإن في بعض الحالات المدرس بيسجّل الدفعات بعد يومين أو أكتر.\n\n"
                    . "📞 لأي استفسار أو متابعة، يُرجى التواصل مباشرة مع **السنتر** على الرقم: 01285527877.\n\n"
                    . "⚠️ هذه الرسالة تم إرسالها **آليًا** من نظام المتابعة، **ولا يمكن الرد عليها أو التواصل من خلالها**.\n"
                    . "شكرًا لتعاونكم 🙏";
            case 'login_notification':
                return "تم تسجيل دخول جديد لحساب {$data['name']} يوم {$data['date']} الساعة {$data['time']}.";
            case 'failed_login_attempt':
                return "محاولة تسجيل دخول فاشلة:
                    - اسم المستخدم: {$data['username']}
                    - نوع المستخدم: {$data['guard']}
                    - عنوان الـ IP: {$data['ip']}
                    - التاريخ: يوم {$data['date']} الساعة {$data['time']}";
            case 'updated_personal_info':
                return "تم تحديث المعلومات الشخصية للمستخدم {$data['name']}.";
            case 'updated_profile_pic':
                return "تم تحديث صورة الملف الشخصي للمستخدم {$data['name']}.\nنوع المستخدم: {$data['model']}";
            case 'security_code_updated':
                return "تم تغيير الكود السري الخاص بحسابك على منصة شطّور \nلو ما كنتش إنت اللي عملت التغيير ده، ابعتلنا دلوقتي!";
            case 'import_main_report':
                return $data['message'];
            case 'birthday_message':
                // $geminiService = app(GeminiService::class);

                // $prompt = str_replace(
                //     ['{name}'],
                //     [$data['name']],
                //     config('prompts.birthday_message')
                // );

                // $aiMessage = $geminiService->generateContent($prompt);

                // if (!empty($aiMessage)) {
                //     return $aiMessage;
                // }

                return "🎉 كل سنة وانت/ي طيب/ه يا {$data['name']} 🎂\n"
                    . "عقبال مليون سنة سعادة ونجاح 🙌✨";
            case 'student_absence_notification':
                $lessonWord = $data['lesson_count'] == 1 ? 'حصة' : ($data['lesson_count'] == 2 ? 'حصتين' : "{$data['lesson_count']} حصص");

                return "السلام عليكم 👋🏻\n\n"
                    . "نحب نبلغ حضرتك إن الطالب/ة {$data['student_name']} كان/ت غائب/ة اليوم عن {$lessonWord} عند {$data['teacher_name']}:\n\n"
                    . "{$data['lessons_list']}\n\n"
                    . "برجاء المتابعة مع الطالب/ة.\n\n"
                    . "📞 لأي استفسار أو توضيح، يُرجى التواصل مباشرة مع **السنتر** على الرقم: 01285527877.\n\n"
                    . "⚠️ هذه الرسالة تم إرسالها **آليًا** من نظام المتابعة، **ولا يمكن الرد عليها أو التواصل من خلالها**.\n"
                    . "شكرًا لتعاونكم 🙏";
            case 'multiple_account_attempt':
                return "محاولة تسجيل دخول لحسابات متعددة:
                    - اسم المستخدم: {$data['username']}
                    - ايدي الجهاز: {$data['device_id']}
                    - عنوان الـ IP: {$data['ip']}
                    - التاريخ: يوم {$data['date']} الساعة {$data['time']}";
            default:
                return "إشعار: رسالة افتراضية";
        }
    }
}