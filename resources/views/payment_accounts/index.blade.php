@extends('layouts.app')

@section('title', 'บัญชีรับเงิน')

@section('content')
<div class="panel">
    <div class="ph">
        <h3>บัญชีรับเงิน <span class="badge neutral">{{ $accounts->count() }} บัญชี</span></h3>
        <button class="btn btn-primary" type="button" onclick="openAccountForm()">+ เพิ่มบัญชี</button>
    </div>
    <div class="pb">
        <p class="helptext" style="font-size:13px;margin-bottom:14px;line-height:1.6;">
            เพิ่มได้ทั้ง <strong>พร้อมเพย์</strong> และ <strong>บัญชีธนาคาร</strong><br>
            ใช้สวิตช์เปิด/ปิด และตั้งบัญชีใดบัญชีหนึ่งเป็น <strong>ค่าเริ่มต้น</strong> สำหรับหน้า POS
        </p>

        @if($accounts->isEmpty())
            <div class="empty">ยังไม่มีบัญชีรับเงิน — กดเพิ่มบัญชีเพื่อเริ่มใช้งาน</div>
        @else
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                    <tr>
                        <th>ชื่อเรียก</th>
                        <th>ประเภท</th>
                        <th>รายละเอียด</th>
                        <th>เปิดใช้</th>
                        <th>ค่าเริ่มต้น</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($accounts as $acc)
                        <tr style="{{ $acc->is_enabled ? '' : 'opacity:.55;' }}">
                            <td>
                                <strong>{{ $acc->displayTitle() }}</strong>
                                @if($acc->is_default)
                                    <span class="badge ok">Default</span>
                                @endif
                            </td>
                            <td>
                                @if($acc->isPromptPay())
                                    <span class="badge info">พร้อมเพย์</span>
                                @else
                                    <span class="badge neutral">ธนาคาร</span>
                                @endif
                            </td>
                            <td class="mono" style="font-size:12.5px;">
                                @if($acc->isPromptPay())
                                    {{ $acc->promptpay_id }}
                                @else
                                    {{ $acc->bank_name }} · {{ $acc->bank_account_name }} · {{ $acc->bank_account_no }}
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('payment-accounts.toggle', $acc) }}">
                                    @csrf
                                    <label class="switch" title="เปิด/ปิด">
                                        <input type="checkbox" onchange="this.form.submit()" @checked($acc->is_enabled)
                                            @disabled($acc->is_default && $acc->is_enabled)>
                                        <span class="slider"></span>
                                    </label>
                                </form>
                            </td>
                            <td>
                                @if($acc->is_default)
                                    <span class="badge ok">ใช้อยู่</span>
                                @elseif($acc->is_enabled)
                                    <form method="POST" action="{{ route('payment-accounts.default', $acc) }}" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-outline btn-sm" type="submit">ตั้งเป็น Default</button>
                                    </form>
                                @else
                                    <span class="helptext">เปิดใช้ก่อน</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-outline btn-sm" type="button" onclick='openAccountForm(@json($acc))'>แก้ไข</button>
                                <form method="POST" action="{{ route('payment-accounts.destroy', $acc) }}" style="display:inline;" onsubmit="return confirmDelete(this, 'ยืนยันลบบัญชีรับเงินนี้?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<style>
.switch{position:relative;display:inline-block;width:46px;height:26px;}
.switch input{opacity:0;width:0;height:0;}
.slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:26px;transition:.2s;}
.slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;}
.switch input:checked + .slider{background:var(--ok);}
.switch input:checked + .slider:before{transform:translateX(20px);}
.switch input:disabled + .slider{opacity:.5;cursor:not-allowed;}
</style>
<script>
const STORE_URL = @json(route('payment-accounts.store'));
const UPDATE_BASE = @json(url('/payment-accounts'));
const BANKS = ['กสิกรไทย','ไทยพาณิชย์','กรุงเทพ','กรุงไทย','กรุงศรีอยุธยา','ทหารไทยธนชาต','ออมสิน','เพื่อการเกษตรและสหกรณ์การเกษตร','ซีไอเอ็มบีไทย','ยูโอบี','อื่นๆ'];

function bankOptions(selected){
  return `<option value="">— เลือกธนาคาร —</option>` + BANKS.map(b =>
    `<option value="${b}" ${selected===b?'selected':''}>${b}</option>`
  ).join('');
}

function syncTypeFields(){
  const type = document.getElementById('acc_type')?.value;
  const pp = document.getElementById('ppFields');
  const bank = document.getElementById('bankFields');
  if(!pp || !bank) return;
  const showPp = type === 'promptpay';
  pp.style.display = showPp ? '' : 'none';
  bank.style.display = showPp ? 'none' : '';
  pp.querySelectorAll('input,select').forEach(el => el.disabled = !showPp);
  bank.querySelectorAll('input,select').forEach(el => el.disabled = showPp);
}

function openAccountForm(acc = null){
  const action = acc ? `${UPDATE_BASE}/${acc.id}` : STORE_URL;
  openModal(`
    <div class="mh"><h3>${acc?'แก้ไขบัญชีรับเงิน':'เพิ่มบัญชีรับเงิน'}</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>
    <form class="mb" method="POST" action="${action}" id="accForm">
      <input type="hidden" name="_token" value="${window.CSRF}">
      ${acc ? '<input type="hidden" name="_method" value="PUT">' : ''}
      <div class="field"><label>ชื่อเรียก (ไม่บังคับ)</label>
        <input name="label" value="${acc?.label||''}" placeholder="เช่น พร้อมเพย์หลัก / บัญชีกสิกร">
      </div>
      <div class="field"><label>ประเภทบัญชี</label>
        <select name="type" id="acc_type" onchange="syncTypeFields()">
          <option value="promptpay" ${!acc || acc.type==='promptpay'?'selected':''}>พร้อมเพย์</option>
          <option value="bank" ${acc?.type==='bank'?'selected':''}>บัญชีธนาคาร</option>
        </select>
      </div>
      <div id="ppFields">
        <div class="field"><label>เบอร์พร้อมเพย์ / เลขบัตรประชาชน</label>
          <input name="promptpay_id" class="mono" value="${acc?.promptpay_id||''}" placeholder="0800562377">
        </div>
      </div>
      <div id="bankFields" style="display:none;">
        <div class="field"><label>ธนาคาร</label>
          <select name="bank_name">${bankOptions(acc?.bank_name||'')}</select>
        </div>
        <div class="row2">
          <div class="field"><label>ชื่อบัญชี</label><input name="bank_account_name" value="${acc?.bank_account_name||''}"></div>
          <div class="field"><label>เลขที่บัญชี</label><input name="bank_account_no" class="mono" value="${acc?.bank_account_no||''}"></div>
        </div>
        <div class="field"><label>เบอร์พร้อมเพย์สำหรับ QR <span style="color:#C1443C;">*</span></label>
          <input name="promptpay_id" class="mono" value="${acc?.promptpay_id||''}" placeholder="0800562377" required>
          <div class="helptext" style="margin-top:6px;">จำเป็น — แอปธนาคารสแกนได้เฉพาะ QR พร้อมเพย์ ใส่เบอร์ที่ผูกกับบัญชีนี้</div>
        </div>
      </div>
      <div style="display:flex;gap:18px;flex-wrap:wrap;margin:8px 0 4px;">
        <label style="display:flex;align-items:center;gap:8px;">
          <input type="checkbox" name="is_enabled" value="1" style="width:auto;" ${!acc || acc.is_enabled ? 'checked' : ''}> เปิดใช้งาน
        </label>
        <label style="display:flex;align-items:center;gap:8px;">
          <input type="checkbox" name="is_default" value="1" style="width:auto;" ${acc?.is_default ? 'checked' : ''}> ตั้งเป็นค่าเริ่มต้น (Default)
        </label>
      </div>
      <p class="helptext" style="font-size:12px;">บัญชี Default จะถูกเลือกอัตโนมัติตอนชำระเงินที่ POS (แท็บโอนเงิน)</p>
      <div class="mf" style="margin:16px -20px -18px;padding:14px 20px;">
        <button class="btn btn-outline" type="button" onclick="closeModal()">ยกเลิก</button>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </div>
    </form>
  `);
  syncTypeFields();
}
</script>
@endpush
