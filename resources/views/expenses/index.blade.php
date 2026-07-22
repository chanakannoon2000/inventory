@extends('layouts.app')

@section('title', 'เบิกรายจ่าย')

@section('content')
<div class="panel">
    <div class="ph">
        <h3>เบิกรายจ่ายร้าน <span class="badge neutral">{{ number_format($expenses->total()) }} รายการ</span></h3>
        <button class="btn btn-primary" type="button" onclick="openExpense()">+ บันทึกเบิกเงิน</button>
    </div>
    <div class="pb">
        <form class="searchbar" method="GET" style="align-items:flex-end;">
            <div style="flex:1;min-width:140px;"><label>จากวันที่</label><input type="date" name="from" value="{{ $from }}"></div>
            <div style="flex:1;min-width:140px;"><label>ถึงวันที่</label><input type="date" name="to" value="{{ $to }}"></div>
            <div style="min-width:160px;">
                <label>หมวด</label>
                <select name="category">
                    <option value="">ทุกหมวด</option>
                    @foreach($categories as $c)
                        <option value="{{ $c }}" @selected($category === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-dark" type="submit">ค้นหา</button>
        </form>

        <div class="cards" style="margin-top:14px;">
            <div class="card warn">
                <div class="lbl">รวมเบิกช่วงนี้</div>
                <div class="val">{{ ($money)($totalAmount) }}</div>
                <div class="sub">{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
            </div>
            @foreach($byCategory->take(3) as $row)
                <div class="card">
                    <div class="lbl">{{ $row->category }}</div>
                    <div class="val" style="font-size:22px;">{{ ($money)($row->total) }}</div>
                </div>
            @endforeach
        </div>

        <div style="overflow-x:auto;margin-top:8px;">
            <table>
                <thead>
                <tr>
                    <th>วันเวลา</th>
                    <th>หมวด</th>
                    <th>รายการ</th>
                    <th>จำนวนเงิน</th>
                    <th>ชำระ</th>
                    <th>คนเบิก</th>
                    <th>บันทึกโดย</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($expenses as $e)
                    <tr>
                        <td class="mono">{{ $e->spent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><span class="badge neutral">{{ $e->category }}</span></td>
                        <td>
                            <strong>{{ $e->title }}</strong>
                            @if($e->note)
                                <div class="helptext">{{ $e->note }}</div>
                            @endif
                        </td>
                        <td class="mono">{{ ($money)($e->amount) }}</td>
                        <td>{{ $e->paymentMethodLabel() }}</td>
                        <td>{{ $e->paid_by_name ?: '—' }}</td>
                        <td>{{ $e->user?->name ?? '—' }}</td>
                        <td>
                            <button class="btn btn-outline btn-sm btn-edit-expense" type="button" data-id="{{ $e->id }}">แก้ไข</button>
                            <form method="POST" action="{{ route('expenses.destroy', $e) }}" style="display:inline;" onsubmit="return confirmDelete(this, 'ยืนยันลบรายจ่ายนี้?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty">ยังไม่มีรายจ่ายในช่วงนี้ — กด “บันทึกเบิกเงิน” เมื่อเบิกไปซื้อกับข้าวหรือของใช้ร้าน</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $expenses->links('pagination.simple') }}
    </div>
</div>
@endsection

@push('scripts')
@php
    $expenseMap = [];
    foreach ($expenses as $e) {
        $expenseMap[(string) $e->id] = [
            'id' => $e->id,
            'spent_at' => $e->spent_at?->format('Y-m-d\TH:i'),
            'category' => $e->category,
            'title' => $e->title,
            'amount' => (float) $e->amount,
            'payment_method' => $e->payment_method,
            'paid_by_name' => $e->paid_by_name,
            'note' => $e->note,
        ];
    }
@endphp
<script>
const EXPENSE_BASE = @json(url('/expenses'));
const CAT_OPTIONS = @json($categories);
window.EXPENSES = @json($expenseMap);

function esc(s){
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
}

function openExpense(item){
  item = item || null;
  const action = item ? (EXPENSE_BASE + '/' + item.id) : EXPENSE_BASE;
  const nowLocal = new Date();
  const pad = n => String(n).padStart(2,'0');
  const defaultAt = nowLocal.getFullYear()+'-'+pad(nowLocal.getMonth()+1)+'-'+pad(nowLocal.getDate())+'T'+pad(nowLocal.getHours())+':'+pad(nowLocal.getMinutes());
  const spentAt = item && item.spent_at ? item.spent_at : defaultAt;
  const catOpts = CAT_OPTIONS.map(c => {
    const sel = item && item.category === c ? ' selected' : '';
    return '<option value="'+esc(c)+'"'+sel+'>'+esc(c)+'</option>';
  }).join('');
  const payCash = !item || item.payment_method !== 'transfer' ? ' selected' : '';
  const payTransfer = item && item.payment_method === 'transfer' ? ' selected' : '';

  openModal(`
    <div class="mh"><h3>${item ? 'แก้ไขรายจ่าย' : 'บันทึกเบิกเงิน'}</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>
    <form class="mb" method="POST" action="${action}">
      <input type="hidden" name="_token" value="${window.CSRF}">
      ${item ? '<input type="hidden" name="_method" value="PUT">' : ''}
      <div class="field"><label>วันเวลา</label><input type="datetime-local" name="spent_at" required value="${esc(spentAt)}"></div>
      <div class="row2">
        <div class="field"><label>หมวด</label><select name="category" required>${catOpts}</select></div>
        <div class="field"><label>ชำระด้วย</label>
          <select name="payment_method" required>
            <option value="cash"${payCash}>เงินสด</option>
            <option value="transfer"${payTransfer}>โอน</option>
          </select>
        </div>
      </div>
      <div class="field"><label>รายการ</label><input name="title" required maxlength="255" value="${esc(item?.title||'')}" placeholder="เช่น ซื้อกับข้าวกลางวัน / น้ำดื่มร้าน"></div>
      <div class="row2">
        <div class="field"><label>จำนวนเงิน (บาท)</label><input name="amount" type="number" step="0.01" min="0.01" required value="${item?.amount != null ? item.amount : ''}"></div>
        <div class="field"><label>คนเบิก / ผู้รับเงิน</label><input name="paid_by_name" value="${esc(item?.paid_by_name||'')}" placeholder="เช่น น้องแมว / ลุงแดง"></div>
      </div>
      <div class="field"><label>หมายเหตุ</label><textarea name="note" rows="2" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)">${esc(item?.note||'')}</textarea></div>
      <div class="helptext" style="margin-bottom:10px;">ใช้บันทึกเงินที่ร้านเบิกออกไปใช้จ่าย ไม่ใช่รายการขายลูกค้า</div>
      <div class="mf" style="margin:16px -20px -18px;">
        <button class="btn btn-outline" type="button" onclick="closeModal()">ยกเลิก</button>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </div>
    </form>`);
}

document.querySelectorAll('.btn-edit-expense').forEach(function(btn){
  btn.addEventListener('click', function(){
    var id = btn.getAttribute('data-id');
    var item = window.EXPENSES && (window.EXPENSES[id] || window.EXPENSES[String(id)]);
    if(!item){ toast('ไม่พบข้อมูลรายจ่าย'); return; }
    openExpense(item);
  });
});
</script>
@endpush
