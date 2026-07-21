@extends('layouts.app')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="panel no-print" style="margin-bottom:14px;">
    <div class="ph dash-filter-ph">
        <h3 style="margin:0;">ภาพรวม · {{ $periodLabel }}</h3>
        <form method="GET" action="{{ route('dashboard') }}" id="dashFilter" class="dash-filter">
            <div class="field">
                <label>ดูแบบ</label>
                <select name="period" id="dashPeriod" onchange="syncDashFilter()">
                    <option value="day" @selected($period==='day')>รายวัน</option>
                    <option value="month" @selected($period==='month')>รายเดือน</option>
                    <option value="year" @selected($period==='year')>รายปี</option>
                </select>
            </div>
            <div class="field" id="wrapDate" style="{{ $period!=='day'?'display:none':'' }}">
                <label>วันที่</label>
                <input type="date" name="date" value="{{ $dateValue }}" onchange="this.form.submit()">
            </div>
            <div class="field" id="wrapMonth" style="{{ $period!=='month'?'display:none':'' }}">
                <label>เดือน</label>
                <input type="month" name="month" value="{{ $monthValue }}" onchange="this.form.submit()">
            </div>
            <div class="field" id="wrapYear" style="{{ $period!=='year'?'display:none':'' }}">
                <label>ปี</label>
                <select name="year" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected((int)$yearValue === (int)$y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field dash-filter-actions">
                <label class="dash-filter-spacer">&nbsp;</label>
                <div class="dash-filter-btns">
                    <button class="btn btn-primary" type="submit">ดูข้อมูล</button>
                    <button class="btn btn-outline" type="button" onclick="window.print()">🖨 พิมพ์</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="dashboardPrint">
    <div class="print-only report-print-head">
        <h1>{{ $shopName ?? 'ร้านวัสดุก่อสร้าง' }}</h1>
        <h2>แดชบอร์ดสรุป · {{ $periodLabel }}</h2>
        <p>พิมพ์เมื่อ {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="cards">
        <div class="card">
            <div class="lbl">ยอดขาย</div>
            <div class="val">{{ ($money)($periodRevenue) }}</div>
            <div class="sub">{{ $periodSales->count() }} บิล · {{ $periodLabel }}</div>
        </div>
        <div class="card ok">
            <div class="lbl">{{ ($canViewCost ?? false) ? 'กำไรขั้นต้น' : 'จำนวนบิล' }}</div>
            <div class="val">{{ ($canViewCost ?? false) ? ($money)($grossProfit) : $periodSales->count() }}</div>
            <div class="sub">{{ ($canViewCost ?? false) ? 'จากต้นทุนขาย '.($money)($periodCost) : 'ยอดขาย '.($money)($periodRevenue) }}</div>
        </div>
        <div class="card blue">
            <div class="lbl">รายการสินค้าทั้งหมด</div>
            <div class="val">{{ $productCount }}</div>
            <div class="sub">ระบบคลังวัสดุก่อสร้าง</div>
        </div>
        <div class="card warn">
            <div class="lbl">สินค้าต่ำกว่าขั้นต่ำ</div>
            <div class="val">{{ $lowStock->count() }}</div>
            <div class="sub">{{ $overStockCount }} รายการเกิน Max</div>
        </div>
    </div>

    <div class="grid dash-grid" style="grid-template-columns:1.4fr 1fr;">
        <div class="panel">
            <div class="ph"><h3>{{ $chartTitle }}</h3></div>
            <div class="pb"><canvas id="revChart" height="130"></canvas></div>
        </div>
        <div class="panel">
            <div class="ph"><h3>สินค้าขายดี · {{ $periodLabel }}</h3></div>
            <div class="pb">
                @if($topProducts->count())
                    <table>
                        <thead><tr><th>สินค้า</th><th>ขายแล้ว</th></tr></thead>
                        <tbody>
                        @foreach($topProducts as $row)
                            <tr>
                                <td>{{ $row->product_name }}</td>
                                <td class="mono">{{ ($fmt)($row->total_qty) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty">ยังไม่มีข้อมูลการขายในช่วงนี้</div>
                @endif
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="ph">
            <h3>สินค้าใกล้หมด / ต้องสั่งเพิ่ม</h3>
            <span class="badge danger">{{ $lowStock->count() }} รายการ</span>
        </div>
        <div class="pb">
            @if($lowStock->count())
                <table>
                    <thead><tr><th>สินค้า</th><th>คงเหลือ</th><th>ขั้นต่ำ</th><th>ผู้จำหน่าย</th><th></th></tr></thead>
                    <tbody>
                    @foreach($lowStock as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td class="mono">{{ ($fmt)($p->stock) }} {{ $p->unit?->name }}</td>
                            <td class="mono">{{ ($fmt)($p->min_stock) }}</td>
                            <td>{{ $p->supplier?->name ?? '—' }}</td>
                            <td><span class="badge danger">ต่ำกว่าขั้นต่ำ</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty">สต๊อกสินค้าทุกรายการอยู่ในเกณฑ์ปกติ</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function syncDashFilter(){
  const period = document.getElementById('dashPeriod').value;
  document.getElementById('wrapDate').style.display = period === 'day' ? '' : 'none';
  document.getElementById('wrapMonth').style.display = period === 'month' ? '' : 'none';
  document.getElementById('wrapYear').style.display = period === 'year' ? '' : 'none';
  document.getElementById('dashFilter').submit();
}
new Chart(document.getElementById('revChart'), {
  type:'bar',
  data:{
    labels: @json($chartLabels),
    datasets:[{label:'ยอดขาย (บาท)', data:@json($chartData), backgroundColor:'#E4602B', borderRadius:5, maxBarThickness:38}]
  },
  options:{
    responsive:true,
    maintainAspectRatio:true,
    animation:false,
    plugins:{legend:{display:false}},
    scales:{y:{beginAtZero:true, ticks:{callback:v=>'฿'+v}}}
  }
});
</script>
@endpush
