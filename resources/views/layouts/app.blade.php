<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'แดชบอร์ด') — {{ $shopName ?? 'คลังสินค้า' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    @stack('head')
</head>
<body>
<div id="pageLoadBar"></div>
<div id="app">
    <aside id="sidebar">
        <div class="brand">
            @if(!empty($shopLogo))
                <img class="brand-logo" src="{{ $shopLogo }}" alt="logo">
            @endif
            <span class="tag">Builder Stock</span>
            <h1 class="brand-title">{{ $shopName ?? 'ร้านวัสดุก่อสร้าง' }}</h1>
            <div class="sub brand-sub">ระบบคลัง + POS หน้าร้าน</div>
        </div>
        <nav class="tabs">
            <div class="grp-label">ภาพรวม</div>
            <a class="navbtn {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="แดชบอร์ด"><span class="ic">▣</span><span class="nav-text">แดชบอร์ด</span></a>
            <a class="navbtn {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}" title="ขายหน้าร้าน (POS)"><span class="ic">฿</span><span class="nav-text">ขายหน้าร้าน (POS)</span></a>
            <a class="navbtn {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}" title="เบิกรายจ่าย"><span class="ic">🧾</span><span class="nav-text">เบิกรายจ่าย</span></a>

            <div class="grp-label">คลังสินค้า</div>
            <a class="navbtn {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}" title="สินค้า / บริการ"><span class="ic">📦</span><span class="nav-text">สินค้า/บริการ</span></a>
            <a class="navbtn {{ request()->routeIs('product-groups.*') ? 'active' : '' }}" href="{{ route('product-groups.index') }}" title="กลุ่มสินค้า"><span class="ic">🧩</span><span class="nav-text">กลุ่มสินค้า</span></a>
            <a class="navbtn {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}" title="หมวดหมู่สินค้า"><span class="ic">📁</span><span class="nav-text">หมวดหมู่สินค้า</span></a>
            <a class="navbtn {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}" title="หน่วยนับ"><span class="ic">📐</span><span class="nav-text">หน่วยนับ</span></a>
            <a class="navbtn {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}" title="ผู้จำหน่าย/Supplier"><span class="ic">🚚</span><span class="nav-text">ผู้จำหน่าย/Supplier</span></a>

            <div class="grp-label">วิเคราะห์</div>
            <a class="navbtn {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" title="รายงาน"><span class="ic">📊</span><span class="nav-text">รายงาน</span></a>
            @if(auth()->user()?->isOwner())
                <a class="navbtn {{ request()->routeIs('payment-accounts.*') ? 'active' : '' }}" href="{{ route('payment-accounts.index') }}" title="บัญชีรับเงิน"><span class="ic">💳</span><span class="nav-text">บัญชีรับเงิน</span></a>
                <a class="navbtn {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}" title="ตั้งค่าระบบ"><span class="ic">⚙</span><span class="nav-text">ตั้งค่าระบบ</span></a>
            @endif
        </nav>
        <div class="foot">
            <div class="foot-user">{{ auth()->user()->name }} · {{ auth()->user()->isOwner() ? 'เจ้าของร้าน' : 'แคชเชียร์' }}</div>
            <form method="POST" action="{{ route('logout') }}" style="margin-top:8px;">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm logout-btn" style="width:100%;color:#fff;border-color:rgba(255,255,255,.2);" title="ออกจากระบบ">
                    <span class="logout-icon">⎋</span><span class="nav-text">ออกจากระบบ</span>
                </button>
            </form>
        </div>
    </aside>
    <div id="main">
        <div id="topbar">
            <div class="topbar-left">
                <button type="button" id="sidebarToggleTop" class="sidebar-toggle" title="ย่อ/ขยายเมนู" aria-label="ย่อ/ขยายเมนู">☰</button>
                <h2>@yield('title', 'แดชบอร์ด')</h2>
            </div>
            <div class="meta" id="topClock">{{ now()->translatedFormat('d M Y H:i') }}</div>
        </div>
        <div id="content">
            @if(session('error'))
                <div class="flash danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="flash danger">
                    @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>

<div class="overlay" id="overlay"><div class="modal" id="modalBox"></div></div>
<div class="toast" id="toast"></div>
<div id="sidebarBackdrop"></div>

<script>
window.CSRF = document.querySelector('meta[name="csrf-token"]').content;
@if(session('success'))
document.addEventListener('DOMContentLoaded', function(){
  if(typeof Swal === 'undefined') return;
  Swal.fire({
    icon: 'success',
    title: @json(session('success')),
    confirmButtonText: 'ตกลง',
    confirmButtonColor: '#E4602B',
    heightAuto: false,
    timer: 2200,
    timerProgressBar: true,
  });
});
@endif
function toast(msg){
  const t=document.getElementById('toast');
  t.textContent=msg; t.classList.add('show');
  clearTimeout(t._h); t._h=setTimeout(()=>t.classList.remove('show'), 2600);
}
function money(n){ return '฿' + Number(n||0).toLocaleString('th-TH',{maximumFractionDigits:2}); }
function fmt(n){ return Number(n||0).toLocaleString('th-TH',{maximumFractionDigits:2}); }
function openModal(html){ document.getElementById('modalBox').innerHTML=html; document.getElementById('overlay').classList.add('show'); }
function closeModal(){ document.getElementById('overlay').classList.remove('show'); }
document.getElementById('overlay')?.addEventListener('click', e => { if(e.target.id==='overlay') closeModal(); });

function confirmDelete(form, message){
  Swal.fire({
    title: 'ยืนยันการลบ?',
    text: message || 'ต้องการลบรายการนี้หรือไม่',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#C1443C',
    cancelButtonColor: '#8a9099',
    reverseButtons: true,
    heightAuto: false,
  }).then(function(result){
    if(result.isConfirmed) form.submit();
  });
  return false;
}

(function(){
  const KEY = 'sidebar-collapsed';
  const sidebar = document.getElementById('sidebar');
  if(!sidebar) return;

  const backdrop = document.getElementById('sidebarBackdrop');
  // จอมือถือ: เมนูจะเป็นแผงเลื่อนเข้า-ออกทับหน้าจอ (off-canvas) แทนการย่อเป็นแถบไอคอน
  const isMobile = () => window.matchMedia('(max-width: 640px)').matches;

  function apply(collapsed){
    sidebar.classList.toggle('collapsed', collapsed);
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    localStorage.setItem(KEY, collapsed ? '1' : '0');
  }

  function openMobile(){
    sidebar.classList.add('mobile-open');
    backdrop?.classList.add('show');
  }
  function closeMobile(){
    sidebar.classList.remove('mobile-open');
    backdrop?.classList.remove('show');
  }

  apply(localStorage.getItem(KEY) === '1');

  function toggle(){
    if(isMobile()){
      sidebar.classList.contains('mobile-open') ? closeMobile() : openMobile();
    } else {
      apply(!sidebar.classList.contains('collapsed'));
    }
  }
  document.getElementById('sidebarToggleTop')?.addEventListener('click', toggle);
  backdrop?.addEventListener('click', closeMobile);
  sidebar.querySelectorAll('a.navbtn').forEach(a => a.addEventListener('click', () => { if(isMobile()) closeMobile(); }));
  window.addEventListener('resize', () => { if(!isMobile()) closeMobile(); });
})();

// แถบโหลดด้านบน: กดเมนู/ลิงก์แล้วขึ้นทันทีเป็น feedback ทันใจ ไม่รู้สึกว่าเว็บค้าง
// เพราะระบบนี้ยังโหลดหน้าใหม่ทั้งหน้า (ไม่ใช่ SPA) ระหว่างรอเซิร์ฟเวอร์ตอบกลับจะรู้สึกลื่นขึ้นมาก
(function(){
  const bar = document.getElementById('pageLoadBar');
  if(!bar) return;
  let width = 0, timer = null;

  function start(){
    clearInterval(timer);
    bar.classList.add('active');
    width = 20;
    bar.style.width = width + '%';
    timer = setInterval(() => {
      width += (90 - width) * 0.1;
      bar.style.width = width + '%';
    }, 200);
  }
  function finish(){
    clearInterval(timer);
    bar.style.width = '100%';
    setTimeout(() => { bar.classList.remove('active'); bar.style.width = '0%'; }, 200);
  }

  start();
  window.addEventListener('load', finish);
  window.addEventListener('pageshow', finish); // เผื่อกดปุ่มย้อนกลับแล้วเบราว์เซอร์ใช้หน้าจาก bfcache

  document.addEventListener('click', function(e){
    const a = e.target.closest('a[href]');
    if(!a || a.target === '_blank' || a.hasAttribute('download')) return;
    const href = a.getAttribute('href') || '';
    if(!href || href.startsWith('#') || href.startsWith('javascript:')) return;
    if(e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if(a.origin && a.origin !== location.origin) return;
    start();
  });
  document.addEventListener('submit', function(e){
    if(e.target.tagName === 'FORM' && !e.defaultPrevented) start();
  });
})();
</script>
{{-- ไลบรารีภายนอกย้ายมาไว้ท้ายหน้าแทนที่จะบล็อกอยู่ใน <head> ทุกหน้า ทำให้หน้าเว็บขึ้นและใช้งานได้เร็วขึ้นทันที
     - SweetAlert2 โหลดทุกหน้าเพราะใช้กับ popup ยืนยัน/แจ้งเตือนแทบทุกที่
     - QRCode/JsBarcode/Chart.js โหลดเฉพาะหน้าที่ใช้จริง ผ่าน @push('vendor_scripts') --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@stack('vendor_scripts')
@stack('scripts')
</body>
</html>
