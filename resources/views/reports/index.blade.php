@extends('layouts.app')

@section('title', 'รายงานยอดขาย')

@section('content')
<div class="panel no-print">
    <div class="ph">
        <h3>ช่วงวันที่</h3>
        <button class="btn btn-outline" type="button" onclick="window.print()">🖨 พิมพ์รายงาน</button>
    </div>
    <div class="pb">
        <form class="searchbar" method="GET" style="align-items:flex-end;">
            <div style="flex:1;min-width:150px;"><label>จากวันที่</label><input type="date" name="from" value="{{ $from }}"></div>
            <div style="flex:1;min-width:150px;"><label>ถึงวันที่</label><input type="date" name="to" value="{{ $to }}"></div>
            <div style="min-width:140px;">
                <label>แสดงต่อหน้า</label>
                <select name="per_page">
                    @foreach([25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected((int) $perPage === $n)>{{ $n }} รายการ</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">ดูรายงาน</button>
        </form>
        <p class="helptext" style="margin-top:10px;font-size:13px;">
            กด <strong>รายการ</strong> เพื่อยกเลิกบางชิ้นในบิล หรือกด <strong>ยกเลิกบิล</strong> เพื่อยกเลิกทั้งใบ — ระบบคืนสต๊อกให้อัตโนมัติ
        </p>
    </div>
</div>

<div id="reportPrint">
    <div class="print-only report-print-head">
        <h1>{{ $shopName ?? 'ร้านวัสดุก่อสร้าง' }}</h1>
        <h2>รายงานยอดขาย</h2>
        <p>ช่วงวันที่ {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</p>
        <p>พิมพ์เมื่อ {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="cards">
        <div class="card"><div class="lbl">ยอดขายรวม</div><div class="val">{{ ($money)($totalRev) }}</div><div class="sub">{{ number_format($activeSalesCount) }} บิล{{ $cancelledCount ? ' · ยกเลิก '.$cancelledCount.' บิล' : '' }}</div></div>
        @if($canViewCost ?? false)
            <div class="card blue"><div class="lbl">ต้นทุนขาย</div><div class="val">{{ ($money)($totalCost) }}</div></div>
            <div class="card ok">
                <div class="lbl">กำไรขั้นต้น</div>
                <div class="val">{{ ($money)($profit) }}</div>
                <div class="sub">{{ $totalRev ? number_format($profit / $totalRev * 100, 1) : 0 }}% margin · ยอดขาย − ต้นทุน</div>
            </div>
        @endif
        <div class="card warn">
            <div class="lbl">เบิกรายจ่าย</div>
            <div class="val">{{ ($money)($totalExpenses ?? 0) }}</div>
            <div class="sub"><a class="no-print" href="{{ route('expenses.index', ['from'=>$from,'to'=>$to]) }}" style="color:inherit;">ดูรายจ่าย →</a></div>
        </div>
        @if($canViewCost ?? false)
            <div class="card {{ ($netAfterExpenses ?? 0) >= 0 ? 'ok' : 'warn' }}">
                <div class="lbl">กำไรสุทธิ</div>
                <div class="val">{{ ($money)($netAfterExpenses ?? $profit) }}</div>
                <div class="sub">กำไรขั้นต้น − รายจ่ายร้าน</div>
            </div>
        @endif
    </div>

    <div class="grid report-grid" style="grid-template-columns:1fr 1.3fr;">
        <div class="panel">
            <div class="ph"><h3>ยอดขายตามหมวดหมู่</h3></div>
            <div class="pb">
                @if($catRev->count())
                    <table>
                        <thead><tr><th>หมวดหมู่</th><th>ยอดขาย</th></tr></thead>
                        <tbody>
                        @foreach($catRev as $row)
                            <tr><td>{{ $row->category_name }}</td><td class="mono">{{ ($money)($row->revenue) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty">ไม่มีข้อมูล</div>
                @endif
            </div>
        </div>
        <div class="panel">
            <div class="ph">
                <h3>รายการบิลขาย</h3>
                <span class="badge neutral no-print">{{ number_format($sales->total()) }} บิล</span>
            </div>
            <div class="pb report-bills">
                @if($sales->count())
                    <table>
                        <thead><tr><th>เลขที่</th><th>วันเวลา</th><th>รายการ</th><th>ยอดรวม</th><th class="no-print"></th></tr></thead>
                        <tbody>
                        @foreach($sales as $s)
                            <tr style="{{ $s->isCancelled() ? 'opacity:.55;' : '' }}">
                                <td class="mono">
                                    {{ $s->receipt_no }}
                                    @if($s->isCancelled())
                                        <span class="badge danger">ยกเลิกแล้ว</span>
                                    @endif
                                    <a class="btn btn-primary btn-sm no-print" href="{{ url('/r/'.$s->id) }}" target="_blank" style="margin-left:6px;">View</a>
                                </td>
                                <td>{{ $s->sold_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $s->items->count() }} รายการ</td>
                                <td class="mono">{{ ($money)($s->total) }}</td>
                                <td class="no-print" style="white-space:nowrap;">
                                    <a class="btn btn-outline btn-sm" href="{{ route('invoices.tax', $s) }}" target="_blank">ใบกำกับภาษี</a>
                                    @unless($s->isCancelled())
                                        <button class="btn btn-outline btn-sm" type="button" onclick="openSaleItems({{ $s->id }})">รายการ</button>
                                        <button class="btn btn-danger btn-sm" type="button"
                                            onclick="cancelSale({{ $s->id }}, @js($s->receipt_no))">ยกเลิกบิล</button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="no-print" style="margin-top:12px;">
                        {{ $sales->links('pagination.numbered') }}
                    </div>
                @else
                    <div class="empty">ยังไม่มีการขายในช่วงนี้</div>
                @endif
            </div>
        </div>
    </div>
</div>

<form id="cancelSaleForm" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="cancel_reason" id="cancelReasonInput">
</form>
@endsection

@push('scripts')
<script>
const SALE_SHOW_URL = @json(url('/sales'));
const moneyJs = n => {
  const v = Number(n||0);
  const hasCents = Math.abs(v - Math.round(v)) > 0.001;
  return '฿' + v.toLocaleString('th-TH', {minimumFractionDigits: hasCents ? 2 : 0, maximumFractionDigits: 2});
};

async function cancelSale(id, receiptNo){
  const result = await Swal.fire({
    title: 'ยกเลิกทั้งบิล?',
    html: `<div style="text-align:left;line-height:1.6">
      ยืนยันยกเลิกบิล <strong>${receiptNo}</strong> ทั้งใบ<br>
      <span style="color:#888;font-size:13px;">ระบบจะคืนสต๊อกให้อัตโนมัติ</span>
    </div>`,
    input: 'text',
    inputLabel: 'เหตุผล (ไม่บังคับ)',
    inputValue: 'ลูกค้าขอยกเลิก',
    inputPlaceholder: 'เช่น ลูกค้าขอยกเลิก',
    showCancelButton: true,
    confirmButtonText: 'ยืนยันยกเลิกทั้งบิล',
    cancelButtonText: 'ปิด',
    confirmButtonColor: '#C1443C',
    cancelButtonColor: '#8a9099',
    reverseButtons: true,
    focusConfirm: false,
  });

  if(!result.isConfirmed) return;

  const form = document.getElementById('cancelSaleForm');
  form.action = SALE_SHOW_URL + '/' + id + '/cancel';
  document.getElementById('cancelReasonInput').value = result.value || '';
  form.submit();
}

async function openSaleItems(saleId){
  const res = await fetch(SALE_SHOW_URL + '/' + saleId, {headers:{'Accept':'application/json'}});
  const data = await res.json();
  if(!data.ok){
    Swal.fire({icon:'error', title:'โหลดบิลไม่สำเร็จ', confirmButtonColor:'#E4602B'});
    return;
  }
  const sale = data.sale;
  const rows = sale.items.map(it => `
    <tr>
      <td>${escapeHtml(it.name)}</td>
      <td class="mono">${it.qty}</td>
      <td class="mono">${moneyJs(it.total)}</td>
      <td>
        <button class="btn btn-danger btn-sm btn-void-item" type="button"
          data-sale-id="${sale.id}"
          data-item-id="${it.id}"
          data-name="${escapeAttr(it.name)}"
          data-qty="${it.qty}">ยกเลิกรายการ</button>
      </td>
    </tr>`).join('');

  openModal(`
    <div class="mh">
      <h3>รายการในบิล ${escapeHtml(sale.receipt_no)}</h3>
      <button class="xbtn" type="button" onclick="closeModal()">✕</button>
    </div>
    <div class="mb">
      <div class="helptext" style="margin-bottom:12px;">${escapeHtml(sale.sold_at)} · ยอดบิล ${moneyJs(sale.total)} · เลือกยกเลิกเฉพาะรายการที่ต้องการ</div>
      ${sale.items.length ? `
        <table>
          <thead><tr><th>สินค้า</th><th>จำนวน</th><th>รวม</th><th></th></tr></thead>
          <tbody>${rows}</tbody>
        </table>` : `<div class="empty">ไม่มีรายการในบิล</div>`}
    </div>
    <div class="mf">
      <button class="btn btn-outline" type="button" onclick="closeModal()">ปิด</button>
    </div>`);

  document.querySelectorAll('#modalBox .btn-void-item').forEach(btn => {
    btn.addEventListener('click', () => {
      voidSaleItem(
        Number(btn.dataset.saleId),
        Number(btn.dataset.itemId),
        btn.dataset.name || '',
        Number(btn.dataset.qty)
      );
    });
  });
}

function escapeHtml(str){
  return String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function escapeAttr(str){
  return String(str ?? '').replace(/"/g, '&quot;');
}

async function voidSaleItem(saleId, itemId, name, maxQty){
  const result = await Swal.fire({
    title: 'ยกเลิกรายการ?',
    html: `<div style="text-align:left;line-height:1.6">สินค้า: <strong>${escapeHtml(name)}</strong><br>
      <span style="color:#888;font-size:13px;">คืนสต๊อกและคำนวณยอดบิลใหม่ให้อัตโนมัติ</span></div>`,
    input: 'number',
    inputLabel: 'จำนวนที่ยกเลิก',
    inputValue: maxQty,
    inputAttributes: { min: 0.01, max: maxQty, step: 'any' },
    inputValidator: (v) => {
      const n = parseFloat(v);
      if(!n || n <= 0) return 'กรอกจำนวนที่ต้องการยกเลิก';
      if(n > maxQty) return 'เกินจำนวนในบิล';
      return null;
    },
    showCancelButton: true,
    confirmButtonText: 'ยืนยันยกเลิกรายการ',
    cancelButtonText: 'ปิด',
    confirmButtonColor: '#C1443C',
    cancelButtonColor: '#8a9099',
    reverseButtons: true,
    heightAuto: false,
  });

  if(!result.isConfirmed) return;

  const qty = parseFloat(result.value);
  const res = await fetch(`${SALE_SHOW_URL}/${saleId}/items/${itemId}/remove`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': window.CSRF || document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({ qty, reason: 'ลูกค้าขอยกเลิกรายการ' }),
  });
  const data = await res.json().catch(() => ({ok:false, message:'ตอบกลับไม่ถูกต้อง'}));
  if(!res.ok || !data.ok){
    await Swal.fire({icon:'error', title: data.message || 'ยกเลิกไม่สำเร็จ', confirmButtonColor:'#E4602B'});
    return;
  }
  await Swal.fire({icon:'success', title: data.message, timer:1600, showConfirmButton:false});
  location.reload();
}
</script>
@endpush
