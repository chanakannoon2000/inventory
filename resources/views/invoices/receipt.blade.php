<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ {{ $sale->receipt_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root{ --ink:#20242A; --muted:#6b7280; --line:#D8D2C4; --accent:#E4602B; --danger:#C1443C; }
        *{box-sizing:border-box;}
        body{margin:0; font-family:'IBM Plex Sans Thai',sans-serif; color:var(--ink); background:#EAE6DD;}
        .wrap{max-width:480px; margin:24px auto; padding:0 16px 40px;}
        .toolbar{display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;}
        .btn{border:none; border-radius:7px; padding:9px 15px; font-size:13.5px; font-weight:600; cursor:pointer; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:6px;}
        .btn-primary{background:var(--accent); color:#fff;}
        .btn-outline{background:#fff; border:1px solid var(--line); color:var(--ink);}
        .receipt{
            background:#fff; border:1px solid var(--line); border-radius:10px; padding:22px 20px;
            box-shadow:0 6px 16px rgba(32,36,42,.08); font-family:'IBM Plex Mono',monospace; font-size:13px;
        }
        .center{text-align:center;}
        .shop-name{font-family:'IBM Plex Sans Thai',sans-serif; font-weight:700; font-size:18px; margin:0 0 4px;}
        .muted{color:var(--muted); font-size:12px; line-height:1.45;}
        .logo{max-width:90px; max-height:70px; object-fit:contain; margin:0 auto 10px; display:block;}
        hr{border:none; border-top:1px dashed #cfc9bb; margin:10px 0;}
        .rline{display:flex; justify-content:space-between; gap:12px; padding:3px 0; line-height:1.4;}
        .rline .nm{flex:1; font-family:'IBM Plex Sans Thai',sans-serif;}
        .ritem{display:grid; grid-template-columns:1fr 52px 72px; gap:6px; padding:3px 0; align-items:start; font-family:'IBM Plex Sans Thai',sans-serif; font-size:13px;}
        .ritem.head{font-weight:700; font-size:12px; color:#6b7280; border-bottom:1px dashed #cfc9bb; padding-bottom:5px; margin-bottom:2px;}
        .ritem .qty,.ritem .price{text-align:right; font-family:'IBM Plex Mono',monospace;}
        .ritem .name{line-height:1.35;}
        .grand{font-weight:700; font-size:15px; margin-top:4px;}
        .badge{display:inline-block; background:#fde8e6; color:var(--danger); padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; margin-top:8px;}
        .foot{margin-top:14px; font-family:'IBM Plex Sans Thai',sans-serif; font-size:12px; color:var(--muted); text-align:center;}
        @media print{
            body{background:#fff;}
            .wrap{margin:0; max-width:none; padding:0;}
            .toolbar{display:none!important;}
            .receipt{border:none; box-shadow:none; border-radius:0; padding:0;}
        }
    </style>
</head>
<body>
@php
    $payLabels = ['cash' => 'เงินสด', 'promptpay' => 'พร้อมเพย์', 'bank' => 'โอนธนาคาร'];
    $payLabel = $payLabels[$sale->payment_method] ?? $sale->payment_method;
@endphp
<div class="wrap">
    <div class="toolbar">
        <button class="btn btn-primary" type="button" onclick="window.print()">🖨 พิมพ์ใบเสร็จ</button>
        @auth
            <a class="btn btn-outline" href="{{ route('invoices.tax', $sale) }}">ใบกำกับภาษี</a>
            <a class="btn btn-outline" href="{{ route('pos.index') }}">กลับ POS</a>
        @endauth
    </div>

    <div class="receipt" id="receiptPrint">
        @if($settings->logoSrc())
            <img class="logo" src="{{ $settings->logoSrc() }}" alt="logo">
        @endif
        <div class="center shop-name">{{ $settings->shop_name }}</div>
        <div class="center muted">
            @if($settings->shop_address){{ $settings->shop_address }}<br>@endif
            @if($settings->shop_phone)โทร. {{ $settings->shop_phone }}@endif
        </div>
        <div class="center" style="margin-top:8px;">
            <div>เลขที่ {{ $sale->receipt_no }}</div>
            <div>{{ $sale->sold_at->format('d/m/Y H:i') }}</div>
            @if($sale->isCancelled())
                <span class="badge">ยกเลิกแล้ว</span>
            @endif
        </div>
        <hr>
        <div class="ritem head"><span>รายการสินค้า</span><span class="qty">จำนวน</span><span class="price">ราคา</span></div>
        @forelse($sale->items as $item)
            <div class="ritem">
                <span class="name">{{ $item->product_name }}</span>
                <span class="qty">{{ rtrim(rtrim(number_format((float)$item->qty, 2, '.', ''), '0'), '.') }}</span>
                <span class="price">{{ number_format($item->lineTotal(), 2) }}</span>
            </div>
        @empty
            <div class="center muted">ไม่มีรายการสินค้า</div>
        @endforelse
        <hr>
        <div class="rline"><span>รวม</span><span>{{ number_format((float)$sale->subtotal, 2) }}</span></div>
        @if((float)$sale->discount > 0)
            <div class="rline"><span>ส่วนลด</span><span>-{{ number_format((float)$sale->discount, 2) }}</span></div>
        @endif
        @if((float)$sale->vat_amount > 0)
            <div class="rline"><span>ก่อน VAT</span><span>{{ number_format((float)$sale->net_amount, 2) }}</span></div>
            <div class="rline"><span>VAT {{ number_format((float)$sale->vat_rate, 0) }}%</span><span>{{ number_format((float)$sale->vat_amount, 2) }}</span></div>
        @endif
        <div class="rline grand"><span>ยอดชำระ</span><span>฿{{ number_format((float)$sale->total, 2) }}</span></div>
        <div class="rline"><span>ชำระโดย</span><span>{{ $payLabel }}</span></div>
        <div class="rline"><span>รับเงิน</span><span>{{ number_format((float)$sale->paid, 2) }}</span></div>
        <div class="rline"><span>เงินทอน</span><span>{{ number_format((float)$sale->change_amount, 2) }}</span></div>
        @if($sale->customer_name)
            <hr>
            <div class="rline"><span>ลูกค้า</span><span style="font-family:'IBM Plex Sans Thai',sans-serif;">{{ $sale->customer_name }}</span></div>
        @endif
        <div class="foot">ขอบคุณที่ใช้บริการ<br>พนักงาน: {{ $sale->user?->name ?: '—' }}</div>
    </div>
</div>
</body>
</html>
