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

        $token = trim((string) $settings->line_channel_token);
        $to = trim((string) $settings->line_target_id);

        if ($token === '' || $to === '') {
            return false;
        }

        $method = match ($sale->payment_method) {
            'promptpay' => 'พร้อมเพย์',
            'bank' => 'โอนธนาคาร',
            default => 'เงินสด',
        };
        $items = $sale->relationLoaded('items')
            ? $sale->items
            : $sale->items()->get();

        $itemLines = $items->take(5)->map(function ($it) {
            return '• '.$it->product_name.' x'.rtrim(rtrim(number_format((float) $it->qty, 2, '.', ''), '0'), '.');
        })->implode("\n");

        if ($items->count() > 5) {
            $itemLines .= "\n• ...อีก ".($items->count() - 5).' รายการ';
        }

        $text = "💰 เงินเข้าบัญชีแล้ว\n"
            ."ร้าน: {$settings->shop_name}\n"
            ."เลขที่: {$sale->receipt_no}\n"
            .'ยอด: ฿'.number_format((float) $sale->total, 2)."\n"
            ."ชำระโดย: {$method}\n"
            .'เวลา: '.$sale->sold_at->timezone(config('app.timezone'))->format('d/m/Y H:i')."\n"
            ."รายการ:\n{$itemLines}";

        return self::push($token, $to, $text);
    }

    public static function sendTest(Setting $settings): array
    {
        $token = trim((string) $settings->line_channel_token);
        $to = trim((string) $settings->line_target_id);

        if ($token === '' || $to === '') {
            return ['ok' => false, 'message' => 'กรุณากรอก Channel Access Token และ User/Group ID'];
        }

        $ok = self::push(
            $token,
            $to,
            "✅ ทดสอบแจ้งเตือน LINE\nร้าน: {$settings->shop_name}\nระบบพร้อมแจ้งเมื่อมีเงินเข้าจากการขาย"
        );

        return $ok
            ? ['ok' => true, 'message' => 'ส่งข้อความทดสอบสำเร็จ ตรวจสอบใน LINE']
            : ['ok' => false, 'message' => 'ส่งไม่สำเร็จ ตรวจ Token / User ID'];
    }

    private static function push(string $token, string $to, string $text): bool
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(8)
                ->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $to,
                    'messages' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('LINE push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('LINE push exception: '.$e->getMessage());

            return false;
        }
    }
}
