<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบกำกับภาษี {{ $sale->receipt_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root{ --ink:#1a1a1a; --muted:#666; --line:#ccc; --accent:#E4602B; }
        *{box-sizing:border-box;}
        body{margin:0; font-family:'IBM Plex Sans Thai',sans-serif; color:var(--ink); background:#EAE6DD;}
        .wrap{max-width:900px; margin:24px auto; padding:0 16px 40px;}
        .toolbar{display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;}
        .btn{border:none; border-radius:7px; padding:9px 15px; font-size:13.5px; font-weight:600; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center;}
        .btn-primary{background:var(--accent); color:#fff;}
        .btn-outline{background:#fff; border:1px solid #D8D2C4; color:#20242A;}
        .invoice{
            background:#fff; border:1px solid #D8D2C4; border-radius:10px; padding:28px 32px;
            box-shadow:0 6px 16px rgba(32,36,42,.08);
        }
        .head{display:flex; justify-content:space-between; gap:20px; border-bottom:2px solid #20242A; padding-bottom:16px; margin-bottom:16px;}
        .shop h1{margin:0 0 6px; font-size:22px;}
        .shop p{margin:0; font-size:13px; color:var(--muted); line-height:1.5;}
        .doc-title{text-align:right;}
        .doc-title .label{font-size:20px; font-weight:700;}
        .doc-title .no{font-family:'IBM Plex Mono',monospace; font-size:14px; margin-top:6px;}
        .meta{display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px; font-size:13.5px;}
        .box{border:1px solid var(--line); border-radius:8px; padding:12px 14px;}
        .box h3{margin:0 0 8px; font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em;}
        .box p{margin:0 0 4px; line-height:1.45;}
        table{width:100%; border-collapse:collapse; font-size:13.5px; margin-bottom:14px;}
        th{text-align:left; font-size:11.5px; text-transform:uppercase; color:var(--muted); border-bottom:2px solid #20242A; padding:8px 6px;}
        td{padding:8px 6px; border-bottom:1px solid #eee; vertical-align:top;}
        .r{text-align:right;}
        .c{text-align:center;}
        .totals{width:280px; margin-left:auto; font-size:13.5px;}
        .totals .row{display:flex; justify-content:space-between; padding:5px 0;}
        .totals .grand{font-size:18px; font-weight:700; border-top:2px solid #20242A; margin-top:6px; padding-top:8px;}
        .sign{display:grid; grid-template-columns:1fr 1fr; gap:40px; margin-top:40px; text-align:center; font-size:13px;}
        .sign .line{border-top:1px solid #999; margin-top:60px; padding-top:8px;}
        .note{font-size:12px; color:var(--muted); margin-top:18px;}
        .flash{background:#E4F3E7; color:#3F8F55; padding:10px 12px; border-radius:8px; margin-bottom:12px; font-size:13.5px;}
        .cust-form{background:#fff; border:1px solid #D8D2C4; border-radius:10px; padding:16px 18px; margin-bottom:14px;}
        .cust-form h3{margin:0 0 10px; font-size:16px;}
        .field{margin-bottom:10px;}
        label{display:block; font-size:12px; color:var(--muted); margin-bottom:4px;}
        input,textarea{width:100%; font-family:inherit; font-size:13.5px; padding:8px 10px; border:1px solid #D8D2C4; border-radius:7px;}
        .row2{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
        @media print{
            body{background:#fff;}
            .wrap{margin:0; max-width:none; padding:0;}
            .toolbar,.cust-form,.flash{display:none!important;}
            .invoice{border:none; box-shadow:none; border-radius:0; padding:0;}
        }
    </style>
</head>
<body>
<div class="wrap">
    @if(session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif

    <div class="toolbar no-print">
        <button class="btn btn-primary" type="button" onclick="window.print()">🖨 พิมพ์ใบกำกับภาษี</button>
        <a class="btn btn-outline" href="{{ route('pos.index') }}">กลับ POS</a>
        <a class="btn btn-outline" href="{{ route('reports.index') }}">รายงาน</a>
    </div>

    <div class="cust-form no-print">
        <h3>ข้อมูลผู้ซื้อ (สำหรับใบกำกับภาษีเต็มรูป)</h3>
        <form method="POST" action="{{ route('invoices.customer', $sale) }}">
            @csrf
            <div class="row2">
                <div class="field"><label>ชื่อลูกค้า / ชื่อบริษัท</label><input name="customer_name" value="{{ old('customer_name', $sale->customer_name) }}"></div>
                <div class="field"><label>เลขประจำตัวผู้เสียภาษี</label><input name="customer_tax_id" class="mono" value="{{ old('customer_tax_id', $sale->customer_tax_id) }}"></div>
            </div>
            <div class="field"><label>ที่อยู่</label><textarea name="customer_address" rows="2">{{ old('customer_address', $sale->customer_address) }}</textarea></div>
            <div class="field" style="max-width:280px;"><label>เบอร์โทร</label><input name="customer_phone" value="{{ old('customer_phone', $sale->customer_phone) }}"></div>
            <button class="btn btn-primary" type="submit">บันทึกข้อมูลลูกค้า</button>
        </form>
    </div>

    @php
        $vatRate = (float) ($sale->vat_rate ?: $settings->tax_rate ?: 7);
        $gross = (float) $sale->total;
        $net = (float) ($sale->net_amount ?: 0);
        $vat = (float) ($sale->vat_amount ?: 0);
        if ($net <= 0 && $gross > 0) {
            $parts = \App\Models\Sale::splitVat($gross, $vatRate);
            $net = $parts['net'];
            $vat = $parts['vat'];
        }
        $payLabel = $sale->payment_method === 'promptpay' ? 'พร้อมเพย์' : 'เงินสด';
    @endphp

    <div class="invoice" id="invoicePrint">
        <div class="head">
            <div class="shop">
                @if($settings->logoSrc())
                    <img src="{{ $settings->logoSrc() }}" alt="logo" style="max-width:110px;max-height:80px;object-fit:contain;margin-bottom:10px;">
                @endif
                <h1>{{ $settings->shop_name }}</h1>
                <p>
                    @if($settings->shop_address){{ $settings->shop_address }}<br>@endif
                    @if($settings->shop_phone)โทร. {{ $settings->shop_phone }}<br>@endif
                    เลขประจำตัวผู้เสียภาษี: {{ $settings->shop_tax_id ?: '—' }}
                </p>
            </div>
            <div class="doc-title">
                <div class="label">ใบกำกับภาษี / ใบเสร็จรับเงิน</div>
                <div class="no">เลขที่ {{ $sale->receipt_no }}</div>
                <div style="margin-top:6px;font-size:13px;">วันที่ {{ $sale->sold_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="meta">
            <div class="box">
                <h3>ผู้ขาย</h3>
                <p><strong>{{ $settings->shop_name }}</strong></p>
                <p>{{ $settings->shop_address ?: '—' }}</p>
                <p>เลขผู้เสียภาษี: {{ $settings->shop_tax_id ?: '—' }}</p>
                <p>โทร: {{ $settings->shop_phone ?: '—' }}</p>
            </div>
            <div class="box">
                <h3>ผู้ซื้อ</h3>
                <p><strong>{{ $sale->customer_name ?: 'ลูกค้าทั่วไป' }}</strong></p>
                <p>{{ $sale->customer_address ?: '—' }}</p>
                <p>เลขผู้เสียภาษี: {{ $sale->customer_tax_id ?: '—' }}</p>
                <p>โทร: {{ $sale->customer_phone ?: '—' }}</p>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th>รายการ</th>
                <th class="c" style="width:70px;">จำนวน</th>
                <th class="r" style="width:110px;">ราคา/หน่วย</th>
                <th class="r" style="width:120px;">จำนวนเงิน</th>
            </tr>
            </thead>
            <tbody>
            @foreach($sale->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="c">{{ number_format((float)$item->qty, $item->qty == floor($item->qty) ? 0 : 2) }}</td>
                    <td class="r">{{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="r">{{ number_format($item->lineTotal(), 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span>รวมเป็นเงิน</span><span>{{ number_format((float)$sale->subtotal, 2) }}</span></div>
            @if((float)$sale->discount > 0)
                <div class="row"><span>ส่วนลด</span><span>-{{ number_format((float)$sale->discount, 2) }}</span></div>
            @endif
            <div class="row"><span>มูลค่าสินค้า (ก่อน VAT)</span><span>{{ number_format($net, 2) }}</span></div>
            <div class="row"><span>ภาษีมูลค่าเพิ่ม {{ number_format($vatRate, 0) }}%</span><span>{{ number_format($vat, 2) }}</span></div>
            <div class="row grand"><span>ยอดรวมทั้งสิ้น</span><span>฿{{ number_format($gross, 2) }}</span></div>
            <div class="row"><span>ชำระโดย</span><span>{{ $payLabel }}</span></div>
            <div class="row"><span>รับเงิน / เงินทอน</span><span>{{ number_format((float)$sale->paid, 2) }} / {{ number_format((float)$sale->change_amount, 2) }}</span></div>
        </div>

        <div class="sign">
            <div>
                <div class="line">ผู้รับสินค้า / ผู้ซื้อ</div>
            </div>
            <div>
                <div class="line">ผู้มีอำนาจลงนาม / ผู้ขาย<br><span style="color:#666;font-size:12px;">{{ $sale->user?->name }}</span></div>
            </div>
        </div>

        <p class="note">
            * ราคาสินค้าที่แสดงเป็นราคา<strong>รวมภาษีมูลค่าเพิ่ม</strong>แล้ว<br>
            * เอกสารนี้ใช้เป็นใบกำกับภาษี / ใบเสร็จรับเงินตามประมวลรัษฎากร
        </p>
    </div>
</div>
</body>
</html>
