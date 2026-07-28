<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Unit;
use App\Support\CostCipher;
use App\Support\ImageUploader;
use App\Support\LineNotifier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::current();

        return view('settings.index', compact('settings'));
    }

    public function updateShop(Request $request)
    {
        $data = $request->validate([
            'shop_name' => 'required|string|max:255',
            'shop_tax_id' => 'nullable|string|max:20',
            'shop_address' => 'nullable|string|max:1000',
            'shop_phone' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'shop_logo' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:8192',
            'clear_logo' => 'nullable|boolean',
        ], [
            'shop_logo.uploaded' => 'อัปโหลดโลโก้ไม่สำเร็จ ไฟล์อาจใหญ่เกินไป',
            'shop_logo.mimes' => 'รองรับเฉพาะ JPG, PNG, GIF, WEBP',
        ]);

        $settings = Setting::current();

        if ($request->hasFile('shop_logo')) {
            // อัปโหลดไฟล์ใหม่ต้องชนะติ๊ก "ลบโลโก้" เสมอ ไม่งั้นผู้ใช้เลือกรูปใหม่แล้วจะได้ไม่มีรูปแทน
            $data['shop_logo'] = ImageUploader::storeLogo(
                $request->file('shop_logo'),
                $settings->shop_logo
            );
        } elseif ($request->boolean('clear_logo')) {
            ImageUploader::clear($settings->shop_logo);
            $data['shop_logo'] = null;
        } else {
            unset($data['shop_logo']);
        }

        unset($data['clear_logo']);
        $settings->update($data);

        return back()->with('success', 'บันทึกข้อมูลร้านค้าแล้ว');
    }

    public function updatePayment(Request $request)
    {
        $data = $request->validate([
            'promptpay_id' => 'nullable|string|max:32',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:32',
        ]);

        $data['promptpay_id'] = preg_replace('/\D+/', '', (string) ($data['promptpay_id'] ?? '')) ?: null;
        $data['bank_account_no'] = preg_replace('/\s+/', '', (string) ($data['bank_account_no'] ?? '')) ?: null;

        Setting::current()->update($data);

        return back()->with('success', 'บันทึกบัญชีรับเงินแล้ว');
    }

    public function updateCipher(Request $request)
    {
        $key = strtoupper(trim((string) $request->input('cipher_key')));

        if (! CostCipher::isValidKey($key)) {
            return back()->with('error', 'ต้องเป็นตัวอักษร 10 ตัวที่ไม่ซ้ำกัน');
        }

        Setting::current()->update(['cipher_key' => $key]);

        return back()->with('success', 'บันทึกรหัสลับต้นทุนแล้ว');
    }

    public function updateLine(Request $request)
    {
        $data = $request->validate([
            'line_enabled' => 'nullable|boolean',
            'line_channel_token' => 'nullable|string|max:2000',
            'line_target_id' => 'nullable|string|max:64',
        ]);

        $token = isset($data['line_channel_token'])
            ? preg_replace('/\s+/', '', trim((string) $data['line_channel_token']))
            : null;

        Setting::current()->update([
            'line_enabled' => $request->boolean('line_enabled'),
            'line_channel_token' => $token ?: null,
            'line_target_id' => isset($data['line_target_id']) ? trim((string) $data['line_target_id']) ?: null : null,
        ]);

        return back()->with('success', 'บันทึกการแจ้งเตือน LINE แล้ว');
    }

    public function testLine()
    {
        $result = LineNotifier::sendTest(Setting::current());

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function exportJson(): StreamedResponse
    {
        $settings = Setting::current()->toArray();
        // ไม่ส่งข้อมูลลับ (โทเคน LINE / รหัสลับต้นทุน) ออกไปในไฟล์แบ็กอัพ
        unset($settings['line_channel_token'], $settings['cipher_key']);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'settings' => $settings,
            'categories' => Category::all()->toArray(),
            'units' => Unit::all()->toArray(),
            'suppliers' => Supplier::all()->toArray(),
            'products' => Product::all()->toArray(),
            'sales' => Sale::with('items')->get()->toArray(),
            'expenses' => Expense::all()->toArray(),
            'payment_accounts' => PaymentAccount::all()->toArray(),
        ];

        $filename = 'inventory-backup-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'products-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['id', 'name', 'barcode', 'category', 'unit', 'supplier', 'cost_price', 'sell_price', 'stock', 'min_stock', 'max_stock']);

            Product::with(['category', 'unit', 'supplier'])->orderBy('id')->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [
                        $p->id,
                        $p->name,
                        $p->barcode,
                        $p->category?->name,
                        $p->unit?->name,
                        $p->supplier?->name,
                        $p->cost_price,
                        $p->sell_price,
                        $p->stock,
                        $p->min_stock,
                        $p->max_stock,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
