@extends('layouts.app')

@section('title', 'ตั้งค่าระบบ')

@section('content')
<div class="panel">
    <div class="ph"><h3>ข้อมูลร้านค้า / ใบกำกับภาษี</h3></div>
    <div class="pb">
        <form method="POST" action="{{ route('settings.shop') }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label>โลโก้ร้าน</label>
                <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                    <div id="logoPreviewWrap">
                        @if($settings->logoSrc())
                            <img id="logoPreview" src="{{ $settings->logoSrc() }}" alt="logo" style="width:120px;height:120px;object-fit:contain;border-radius:12px;background:#fff;border:1px solid var(--line);padding:6px;">
                        @else
                            <div id="logoPreview" style="width:120px;height:120px;border-radius:12px;background:var(--concrete-deep);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:36px;">🏪</div>
                        @endif
                    </div>
                    <div style="flex:1;min-width:200px;">
                        <input type="file" name="shop_logo" id="shopLogoInput" accept="image/jpeg,image/png,image/webp,image/gif">
                        @if($settings->shop_logo)
                            <label style="margin-top:8px;display:flex;gap:6px;align-items:center;">
                                <input type="checkbox" name="clear_logo" value="1" style="width:auto;"> ลบโลโก้
                            </label>
                        @endif
                        <div class="helptext">รองรับ JPG/PNG — จะแสดงบน sidebar และใบกำกับภาษี</div>
                    </div>
                </div>
            </div>
            <div class="row2">
                <div class="field"><label>ชื่อร้าน / ชื่อผู้เสียภาษี</label><input name="shop_name" value="{{ $settings->shop_name }}" required></div>
                <div class="field"><label>เลขประจำตัวผู้เสียภาษี</label><input name="shop_tax_id" class="mono" value="{{ $settings->shop_tax_id }}" placeholder="เช่น 0-1234-56789-01-2"></div>
            </div>
            <div class="field">
                <label>ที่อยู่ร้าน</label>
                <textarea name="shop_address" rows="2" placeholder="บ้านเลขที่ ถนน ตำบล อำเภอ จังหวัด รหัสไปรษณีย์">{{ $settings->shop_address }}</textarea>
            </div>
            <div class="row2">
                <div class="field"><label>เบอร์โทรร้าน</label><input name="shop_phone" value="{{ $settings->shop_phone }}" placeholder="เช่น 02-xxx-xxxx"></div>
                <div class="field"><label>อัตราภาษีมูลค่าเพิ่ม (%)</label><input name="tax_rate" type="number" step="0.01" value="{{ $settings->tax_rate }}"></div>
            </div>
            <button class="btn btn-primary" type="submit">บันทึกข้อมูลร้าน</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="ph"><h3>บัญชีรับเงิน</h3></div>
    <div class="pb">
        <p class="helptext" style="font-size:13px;margin-bottom:12px;line-height:1.6;">
            เพิ่ม/แก้ไข พร้อมเพย์ หรือบัญชีธนาคารได้หลายบัญชี มีสวิตช์เปิด-ปิด และตั้งค่า Default
        </p>
        <a class="btn btn-primary" href="{{ route('payment-accounts.index') }}">จัดการบัญชีรับเงิน →</a>
    </div>
</div>

<div class="panel">
    <div class="ph"><h3>รหัสลับราคาต้นทุน (Cost Cipher)</h3></div>
    <div class="pb">
        <p class="helptext" style="font-size:13px;margin-bottom:10px;">กำหนดตัวอักษร 10 ตัวที่ไม่ซ้ำกัน แทนเลข 0–9 เพื่อเข้ารหัสราคาทุนเป็นตัวอักษร ให้เฉพาะผู้ที่รู้กุญแจถอดรหัสได้</p>
        <form method="POST" action="{{ route('settings.cipher') }}">
            @csrf
            <div class="field">
                <label>คีย์เข้ารหัส (10 ตัวอักษร ไม่ซ้ำ)</label>
                <input name="cipher_key" class="mono tag-cipher" maxlength="10" value="{{ $settings->cipher_key }}" style="text-transform:uppercase;" required>
            </div>
            <div class="helptext">ตัวอย่าง: ทุน <span class="mono">145</span> บาท → รหัส <span class="mono tag-cipher" id="cipherPreview">{{ \App\Support\CostCipher::encode(145) }}</span></div>
            <button class="btn btn-primary" style="margin-top:12px;" type="submit">บันทึกรหัส</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="ph"><h3>แจ้งเตือน LINE เมื่อเงินเข้า</h3></div>
    <div class="pb">
        <p class="helptext" style="font-size:13px;margin-bottom:12px;line-height:1.6;">
            เมื่อกดยืนยันชำระเงินที่ POS ระบบจะส่งข้อความเข้า LINE ว่ามีเงินเข้าบัญชีแล้ว<br>
            ใช้ <strong>LINE Messaging API</strong> (Official Account)
        </p>
        <form method="POST" action="{{ route('settings.line') }}">
            @csrf
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                <input type="checkbox" name="line_enabled" value="1" style="width:auto;" @checked($settings->line_enabled)>
                เปิดแจ้งเตือน LINE
            </label>
            <div class="field">
                <label>Channel Access Token</label>
                <textarea name="line_channel_token" rows="2" placeholder="วาง Channel Access Token จาก LINE Developers">{{ $settings->line_channel_token }}</textarea>
            </div>
            <div class="field">
                <label>User ID หรือ Group ID ที่จะรับแจ้งเตือน</label>
                <input name="line_target_id" class="mono" value="{{ $settings->line_target_id }}" placeholder="เช่น Uxxxxxxxx หรือ Cxxxxxxxx">
                <div class="helptext">
                    วิธีหา User ID: เพิ่มเพื่อน OA แล้วดูจาก webhook / LINE Developers → Messaging API → Your user ID<br>
                    หรือสร้างกลุ่ม เชิญบอทเข้ากลุ่ม แล้วใช้ Group ID
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-primary" type="submit">บันทึก LINE</button>
            </div>
        </form>
        <form method="POST" action="{{ route('settings.line-test') }}" style="margin-top:10px;">
            @csrf
            <button class="btn btn-outline" type="submit">ส่งข้อความทดสอบ</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="ph"><h3>สำรองข้อมูล / Export</h3></div>
    <div class="pb">
        <p class="helptext" style="font-size:13px;margin-bottom:12px;">ส่งออกข้อมูลเป็น JSON หรือ CSV สำหรับสำรอง/นำไปใช้ต่อ</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn btn-outline" href="{{ route('backup.json') }}">⬇ ส่งออก JSON</a>
            <a class="btn btn-outline" href="{{ route('backup.csv') }}">⬇ ส่งออกสินค้า CSV</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('input[name=cipher_key]')?.addEventListener('input', e => {
  const key = e.target.value.toUpperCase();
  const prev = document.getElementById('cipherPreview');
  if(prev && key.length >= 10){
    prev.textContent = '145'.split('').map(d => key[parseInt(d,10)] || '?').join('');
  }
});

document.getElementById('shopLogoInput')?.addEventListener('change', function(e){
  const file = e.target.files && e.target.files[0];
  const wrap = document.getElementById('logoPreviewWrap');
  if(!file || !wrap) return;
  const url = URL.createObjectURL(file);
  wrap.innerHTML = `<img id="logoPreview" src="${url}" alt="logo" style="width:120px;height:120px;object-fit:contain;border-radius:12px;background:#fff;border:1px solid var(--line);padding:6px;">`;
});
</script>
@endpush
