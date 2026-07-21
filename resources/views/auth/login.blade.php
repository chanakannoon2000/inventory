<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ — คลังวัสดุก่อสร้าง</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&family=IBM+Plex+Sans+Thai:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="tag" style="display:inline-block;background:var(--orange);color:#1a1a1a;font-weight:800;font-family:'Barlow Condensed',sans-serif;font-size:12px;letter-spacing:.12em;padding:2px 7px;border-radius:3px;margin-bottom:10px;">BUILDER STOCK</div>
        <h1>เข้าสู่ระบบ</h1>
        <p class="helptext" style="margin-bottom:18px;">ระบบจัดการคลังสินค้าและขายหน้าร้านวัสดุก่อสร้าง</p>

        @if($errors->any())
            <div class="flash danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label>อีเมล</label>
                <input type="email" name="email" value="{{ old('email', 'owner@shop.local') }}" required autofocus>
            </div>
            <div class="field">
                <label>รหัสผ่าน</label>
                <input type="password" name="password" value="owner123" required>
            </div>
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <input type="checkbox" name="remember" value="1" style="width:auto;"> จดจำการเข้าสู่ระบบ
            </label>
            <button class="btn btn-primary" style="width:100%;justify-content:center;" type="submit">เข้าสู่ระบบ</button>
        </form>

        <div class="helptext" style="margin-top:18px; line-height:1.6;">
            <strong>บัญชีทดสอบ</strong><br>
            เจ้าของร้าน: owner@shop.local / owner123<br>
            แคชเชียร์: cashier@shop.local / cashier123
        </div>
    </div>
</div>
</body>
</html>
