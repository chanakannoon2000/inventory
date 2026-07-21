<?php

namespace App\Http\Controllers;

use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentAccountController extends Controller
{
    public function index()
    {
        $accounts = PaymentAccount::orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('payment_accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $account = PaymentAccount::create($data + [
                'sort_order' => (int) PaymentAccount::max('sort_order') + 1,
            ]);

            if ($account->is_default || PaymentAccount::count() === 1) {
                PaymentAccount::makeDefault($account);
            }
        });

        return back()->with('success', 'เพิ่มบัญชีรับเงินแล้ว');
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        $data = $this->validated($request, $paymentAccount->id);

        DB::transaction(function () use ($data, $paymentAccount) {
            $paymentAccount->update($data);

            if (! empty($data['is_default'])) {
                PaymentAccount::makeDefault($paymentAccount->fresh());
            }
        });

        return back()->with('success', 'แก้ไขบัญชีรับเงินแล้ว');
    }

    public function destroy(PaymentAccount $paymentAccount)
    {
        $wasDefault = $paymentAccount->is_default;
        $paymentAccount->delete();

        if ($wasDefault) {
            $next = PaymentAccount::enabled()->orderBy('sort_order')->orderBy('id')->first();
            if ($next) {
                PaymentAccount::makeDefault($next);
            }
        }

        return back()->with('success', 'ลบบัญชีรับเงินแล้ว');
    }

    public function toggle(PaymentAccount $paymentAccount)
    {
        if ($paymentAccount->is_default && $paymentAccount->is_enabled) {
            return back()->with('error', 'ไม่สามารถปิดบัญชีที่เป็นค่าเริ่มต้นได้ — ตั้งบัญชีอื่นเป็นค่าเริ่มต้นก่อน');
        }

        $paymentAccount->update(['is_enabled' => ! $paymentAccount->is_enabled]);

        return back()->with('success', $paymentAccount->is_enabled ? 'เปิดใช้งานบัญชีแล้ว' : 'ปิดใช้งานบัญชีแล้ว');
    }

    public function setDefault(PaymentAccount $paymentAccount)
    {
        PaymentAccount::makeDefault($paymentAccount);

        return back()->with('success', 'ตั้งเป็นบัญชีเริ่มต้นแล้ว');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'label' => 'nullable|string|max:255',
            'type' => 'required|in:promptpay,bank',
            'promptpay_id' => 'nullable|string|max:32',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:32',
            'is_enabled' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $data['is_enabled'] = $request->has('is_enabled');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['type'] === 'promptpay') {
            $data['promptpay_id'] = preg_replace('/\D+/', '', (string) ($data['promptpay_id'] ?? ''));
            if ($data['promptpay_id'] === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'promptpay_id' => 'กรุณากรอกเบอร์พร้อมเพย์ / เลขบัตร',
                ]);
            }
            $data['bank_name'] = null;
            $data['bank_account_name'] = null;
            $data['bank_account_no'] = null;
        } else {
            $data['bank_account_no'] = preg_replace('/\s+/', '', (string) ($data['bank_account_no'] ?? ''));
            if (($data['bank_name'] ?? '') === '' || ($data['bank_account_no'] ?? '') === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bank_account_no' => 'กรุณากรอกธนาคารและเลขบัญชี',
                ]);
            }
            // แอปธนาคารสแกนได้เฉพาะ QR พร้อมเพย์ — บังคับใส่เบอร์สำหรับสร้าง QR
            $data['promptpay_id'] = preg_replace('/\D+/', '', (string) ($data['promptpay_id'] ?? ''));
            if ($data['promptpay_id'] === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'promptpay_id' => 'กรุณาใส่เบอร์พร้อมเพย์สำหรับสร้าง QR (แอปธนาคารสแกนเลขบัญชีตรงๆ ไม่ได้)',
                ]);
            }
        }

        return $data;
    }
}
