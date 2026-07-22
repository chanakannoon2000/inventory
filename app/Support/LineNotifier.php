<?php

namespace App\Support;

use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineNotifier
{
    public static function notifyPayment(Sale $sale, ?Setting $settings = null): bool
    {
        $settings ??= Setting::current();

        if (! $settings->line_enabled) {
            return false;
        }

        $token = self::cleanToken((string) $settings->line_channel_token);
        $to = trim((string) $settings->line_target_id);

        if ($token === '' || $to === '') {
            return false;
        }

        $method = match ($sale->payment_method) {
            'promptpay' => 'พร้อมเพย์',
            'bank' => 'โอนธนาคาร',
            default => 'เงินสด',
        };

        $text = "💰 เงินเข้าบัญชีแล้ว\n"
            ."ร้าน: {$settings->shop_name}\n"
            ."เลขที่: {$sale->receipt_no}\n"
            .'ยอด: ฿'.number_format((float) $sale->total, 2)."\n"
            ."ชำระโดย: {$method}\n"
            .'เวลา: '.$sale->sold_at->timezone(config('app.timezone'))->format('d/m/Y H:i');

        return self::push($token, $to, $text)['ok'];
    }

    public static function sendTest(Setting $settings): array
    {
        $token = self::cleanToken((string) $settings->line_channel_token);
        $to = trim((string) $settings->line_target_id);

        if ($token === '' || $to === '') {
            return ['ok' => false, 'message' => 'กรุณากรอก Channel Access Token และ User/Group ID'];
        }

        $result = self::push(
            $token,
            $to,
            "✅ ทดสอบแจ้งเตือน LINE\nร้าน: {$settings->shop_name}\nระบบพร้อมแจ้งเมื่อมีเงินเข้าจากการขาย"
        );

        return $result['ok']
            ? ['ok' => true, 'message' => 'ส่งข้อความทดสอบสำเร็จ ตรวจสอบใน LINE']
            : ['ok' => false, 'message' => $result['message'] ?: 'ส่งไม่สำเร็จ ตรวจ Token / User ID'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    private static function push(string $token, string $to, string $text): array
    {
        try {
            $request = Http::withToken($token)
                ->acceptJson()
                ->timeout(12);

            // Laragon/Windows มักขาด CA bundle → SSL verify พังตอนเรียก api.line.me
            if (app()->environment('local') || config('app.line_http_insecure', false)) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post('https://api.line.me/v2/bot/message/push', [
                'to' => $to,
                'messages' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ]);

            if (! $response->successful()) {
                $body = $response->json();
                $apiMessage = is_array($body)
                    ? (string) ($body['message'] ?? $response->body())
                    : $response->body();

                Log::warning('LINE push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'ok' => false,
                    'message' => 'LINE ตอบกลับ error ('.$response->status().'): '.$apiMessage,
                ];
            }

            return ['ok' => true, 'message' => 'ok'];
        } catch (\Throwable $e) {
            Log::warning('LINE push exception: '.$e->getMessage());

            $msg = $e->getMessage();
            if (str_contains($msg, 'SSL certificate') || str_contains($msg, 'cURL error 60')) {
                return [
                    'ok' => false,
                    'message' => 'เชื่อมต่อ LINE ไม่ได้เพราะ SSL บนเครื่องนี้ (คัดลอก Token ใหม่แล้วลองอีกครั้ง หรือติดตั้ง CA certificate)',
                ];
            }

            return ['ok' => false, 'message' => 'เชื่อมต่อ LINE ไม่ได้: '.$msg];
        }
    }

    private static function cleanToken(string $token): string
    {
        return preg_replace('/\s+/', '', trim($token)) ?? '';
    }
}
