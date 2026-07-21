<?php

namespace App\Support;

class PromptPay
{
    /**
     * สร้าง payload EMVCo สำหรับสแกนจ่ายพร้อมเพย์ (เบอร์มือถือ / เลขบัตร / e-Wallet)
     */
    public static function payload(string $id, float|int|string|null $amount = null): string
    {
        $id = preg_replace('/\D+/', '', $id) ?: '';
        if ($id === '') {
            return '';
        }

        $target = self::normalizeTarget($id);
        $merchantInfo = self::tlv('00', 'A000000677010111').self::tlv('01', $target);

        return self::build($merchantInfo, $amount);
    }

    /**
     * PromptPay โอนเข้าเลขบัญชีธนาคาร (tag 29 / sub-tag 04)
     */
    public static function bankAccountPayload(string $bankCode, string $accountNo, float|int|string|null $amount = null): string
    {
        $code = preg_replace('/\D+/', '', $bankCode) ?: '';
        $acct = preg_replace('/\D+/', '', $accountNo) ?: '';
        if (strlen($code) !== 3 || $acct === '') {
            return '';
        }

        $merchantInfo = self::tlv('00', 'A000000677010111').self::tlv('04', $code.$acct);

        return self::build($merchantInfo, $amount);
    }

    private static function build(string $merchantInfo, float|int|string|null $amount = null): string
    {
        $hasAmount = $amount !== null && (float) $amount > 0;

        $payload = self::tlv('00', '01')
            .self::tlv('01', $hasAmount ? '12' : '11')
            .self::tlv('29', $merchantInfo)
            .self::tlv('53', '764');

        if ($hasAmount) {
            $payload .= self::tlv('54', number_format((float) $amount, 2, '.', ''));
        }

        $payload .= self::tlv('58', 'TH');
        $payload .= '6304';

        return $payload.self::crc16($payload);
    }

    private static function normalizeTarget(string $id): string
    {
        // เบอร์มือถือ 0XXXXXXXXX → 0066XXXXXXXXX
        if (strlen($id) === 10 && str_starts_with($id, '0')) {
            return '0066'.substr($id, 1);
        }

        // เบอร์ที่ขึ้นต้น 66 แล้ว
        if (strlen($id) === 11 && str_starts_with($id, '66')) {
            return '00'.$id;
        }

        // เลขบัตรประชาชน 13 หลัก / อื่นๆ
        return $id;
    }

    private static function tlv(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    private static function crc16(string $data): string
    {
        $crc = 0xFFFF;
        $len = strlen($data);

        for ($i = 0; $i < $len; $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
