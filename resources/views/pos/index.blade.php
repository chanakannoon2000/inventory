@extends('layouts.app')

@section('title', 'ขายหน้าร้าน (POS)')

@section('content')
<div class="pos-wrap">
    <section class="pos-catalog">
        <div class="pos-toolbar">
            <div class="pos-scan-field">
                <label for="scanInput">สแกนบาร์โค้ด</label>
                <input id="scanInput" class="pos-input pos-input-lg" placeholder="สแกนหรือพิมพ์บาร์โค้ด แล้วกด Enter" autocomplete="off" autofocus>
            </div>
            <div class="pos-scan-field">
                <label for="posSearch">ค้นหาสินค้า</label>
                <input id="posSearch" class="pos-input" placeholder="ชื่อสินค้า / บาร์โค้ด" oninput="filterPos()">
            </div>
            <div class="pos-scan-field">
                <label for="posCatFilter">หมวดหมู่</label>
                <select id="posCatFilter" class="pos-input" onchange="filterPos()">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->barcode_prefix ? $c->barcode_prefix.' · ' : '' }}{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="pos-grid" id="posGrid">
            @forelse($posCards as $card)
                @if($card['type'] === 'group')
                    <button type="button"
                         class="pcard pcard-group {{ $card['low'] ? 'low' : '' }} {{ $card['soldout'] ? 'soldout' : '' }}"
                         data-type="group"
                         data-group-key="{{ $card['group_key'] }}"
                         data-name="{{ $card['group_name'] }}"
                         data-barcode="{{ collect($card['variants'])->pluck('barcode')->implode(' ') }}"
                         data-search="{{ $card['group_name'] }} {{ collect($card['variants'])->pluck('size')->implode(' ') }} {{ collect($card['variants'])->pluck('barcode')->implode(' ') }}"
                         data-cat="{{ $card['category_id'] }}"
                         onclick="openSizePicker(this)"
                         @disabled($card['soldout'])>
                        @if($card['image'])
                            <img class="pimg" src="{{ $card['image'] }}" alt="{{ $card['group_name'] }}" loading="lazy">
                        @else
                            <div class="pimg ph-icon" style="background:{{ $card['color'] }} !important;">{{ $card['icon'] }}</div>
                        @endif
                        <div class="pcard-body">
                            <div class="nm">{{ $card['group_name'] }}</div>
                            <div class="size-tag">{{ $card['size_count'] }} ไซส์ · เลือกขนาด</div>
                            <div class="pr">
                                @if($card['min_price'] == $card['max_price'])
                                    {{ ($money)($card['min_price']) }}
                                @else
                                    {{ ($money)($card['min_price']) }}–{{ ($money)($card['max_price']) }}
                                @endif
                            </div>
                            <div class="st {{ $card['low'] ? 'warn' : '' }}">
                                @if($card['soldout'])
                                    หมดสต๊อกทุกไซส์
                                @else
                                    มี {{ $card['available_count'] }}/{{ $card['size_count'] }} ไซส์
                                @endif
                            </div>
                        </div>
                    </button>
                @else
                    @php
                        $p = $card['product'];
                        $isService = $p->isService();
                        $soldOut = ! $isService && (float) $p->stock <= 0;
                    @endphp
                    <button type="button"
                         class="pcard {{ $isService ? 'pcard-service' : '' }} {{ $p->isLowStock() ? 'low' : '' }} {{ $soldOut ? 'soldout' : '' }}"
                         data-type="single"
                         data-item-type="{{ $p->type ?: 'product' }}"
                         data-id="{{ $p->id }}"
                         data-name="{{ $p->name }}"
                         data-barcode="{{ $p->barcode }}"
                         data-search="{{ $p->name }} {{ $p->barcode }}"
                         data-price="{{ $p->sell_price }}"
                         data-stock="{{ $isService ? 999999 : $p->stock }}"
                         data-unit="{{ $p->unit?->name }}"
                         data-cat="{{ $p->category_id }}"
                         data-image="{{ $p->imageSrc() }}"
                         data-icon="{{ $isService ? '🚚' : $p->placeholderIcon() }}"
                         data-color="{{ $p->placeholderColor() }}"
                         data-is-service="{{ $isService ? '1' : '0' }}"
                         onclick="addToCartFromCard(this)"
                         @disabled($soldOut)>
                        @if($p->imageSrc())
                            <img class="pimg" src="{{ $p->imageSrc() }}" alt="{{ $p->name }}" loading="lazy">
                        @else
                            <div class="pimg ph-icon" style="background:{{ $p->placeholderColor() }} !important;">{{ $isService ? '🚚' : $p->placeholderIcon() }}</div>
                        @endif
                        <div class="pcard-body">
                            <div class="nm">{{ $p->name }}</div>
                            <div class="pr">{{ ($money)($p->sell_price) }}</div>
                            <div class="st {{ $p->isLowStock() ? 'warn' : '' }}">
                                @if($isService)
                                    บริการ · ไม่ตัดสต๊อก
                                @elseif((float)$p->stock <= 0)
                                    หมดสต๊อก
                                @elseif($p->isLowStock())
                                    เหลือ {{ ($fmt)($p->stock) }} {{ $p->unit?->name }}
                                @else
                                    คงเหลือ {{ ($fmt)($p->stock) }} {{ $p->unit?->name }}
                                @endif
                            </div>
                        </div>
                    </button>
                @endif
            @empty
                <div class="empty pos-empty">ยังไม่มีสินค้าในระบบ</div>
            @endforelse
        </div>
    </section>

    <aside class="cart">
        <div class="cart-head">
            <h3>รายการขาย</h3>
            <span class="cart-count" id="cartCount">0 รายการ</span>
        </div>
        <div class="cart-items" id="cartItems"></div>
        <div class="cart-foot">
            <div id="cartTotals"></div>
            <div class="cart-actions">
                <button class="btn btn-outline" type="button" onclick="clearCart()">ล้างรายการ</button>
                <button class="btn btn-primary cart-pay" type="button" id="payBtn" onclick="openCheckout()" disabled>ชำระเงิน</button>
            </div>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
@php
    $posGroupMap = [];
    foreach ($posCards as $card) {
        if (($card['type'] ?? '') === 'group') {
            $posGroupMap[$card['group_key']] = [
                'name' => $card['group_name'],
                'variants' => $card['variants'],
            ];
        }
    }
@endphp
<script>
const CHECKOUT_URL = @json(route('pos.checkout'));
const BARCODE_URL = @json(route('pos.barcode'));
const PAYMENT_ACCOUNTS = @json($paymentAccounts);
const DEFAULT_ACCOUNT_ID = @json($defaultPayment?->id);
const VAT_RATE = @json((float) ($settings->tax_rate ?? 7));
const POS_GROUPS = @json($posGroupMap);
let cart = [];
let payMethod = 'cash';
let selectedAccountId = DEFAULT_ACCOUNT_ID;

function getSelectedAccount(){
  return PAYMENT_ACCOUNTS.find(a => a.id === selectedAccountId) || PAYMENT_ACCOUNTS[0] || null;
}

// ป้องกัน XSS: ชื่อสินค้า/บัญชี/ที่อยู่รูปมาจากข้อมูลที่ผู้ใช้กรอกได้ ต้อง escape ก่อนใส่ลงใน innerHTML เสมอ
function escHtml(v){
  return String(v == null ? '' : v)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}

function productThumb(p, cls){
  if(p.image) return `<img class="${cls}" src="${escHtml(p.image)}" alt="">`;
  return `<div class="${cls} ph-icon">${escHtml(p.icon||'📦')}</div>`;
}

function addToCartFromCard(el){
  const isService = el.dataset.isService === '1' || el.dataset.itemType === 'service';
  addToCart({
    id: Number(el.dataset.id),
    name: el.dataset.name,
    price: Number(el.dataset.price),
    stock: isService ? 999999 : Number(el.dataset.stock),
    unit: el.dataset.unit||'',
    image: el.dataset.image||'',
    icon: el.dataset.icon||'📦',
    color: el.dataset.color||'#E3DFD3',
    is_service: isService,
  });
}

function openSizePicker(el){
  const key = el.dataset.groupKey || '';
  const group = POS_GROUPS[key] || null;
  const variants = (group && Array.isArray(group.variants)) ? group.variants : [];
  const title = (group && group.name) || el.dataset.name || 'เลือกไซส์';
  if(!variants.length){
    toast('ไม่พบไซส์ในกลุ่มนี้');
    console.warn('POS group missing', key, POS_GROUPS);
    return;
  }
  window._pickerVariants = variants;

  const rows = variants.map((v, i) => {
    const isService = !!v.is_service || v.item_type === 'service';
    const disabled = !isService && Number(v.stock) <= 0;
    const stockTxt = isService
      ? 'บริการ'
      : (disabled
        ? 'หมดสต๊อก'
        : ('เหลือ ' + Number(v.stock).toLocaleString('th-TH') + (v.unit ? ' '+v.unit : '')));
    return `
      <button type="button" class="size-opt ${disabled ? 'disabled' : ''} ${v.low ? 'low' : ''}"
        ${disabled ? 'disabled' : ''}
        onclick="pickSizeVariant(${i})">
        <span class="size-opt-label">${escHtml(v.size || v.name)}</span>
        <span class="size-opt-price mono">${money(v.price)}</span>
        <span class="size-opt-stock ${v.low || disabled ? 'warn' : ''}">${escHtml(stockTxt)}</span>
      </button>`;
  }).join('');

  openModal(`
    <div class="mh"><h3>${escHtml(title)}</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>
    <div class="mb">
      <div class="helptext" style="margin-bottom:10px;">เลือกขนาดที่ต้องการ</div>
      <div class="size-opt-list">${rows}</div>
    </div>
  `);
}

function pickSizeVariant(index){
  const v = (window._pickerVariants || [])[index];
  const isService = !!(v && (v.is_service || v.item_type === 'service'));
  if(!v || (!isService && Number(v.stock) <= 0)){ toast('สินค้าหมดสต๊อก'); return; }
  closeModal();
  addToCart({
    id: Number(v.id),
    name: v.name,
    price: Number(v.price),
    stock: isService ? 999999 : Number(v.stock),
    unit: v.unit || '',
    image: v.image || '',
    icon: v.icon || '📦',
    color: v.color || '#E3DFD3',
    is_service: isService,
  });
}

function addToCart(p){
  const isService = !!p.is_service;
  if(!isService && p.stock <= 0){ toast('สินค้าหมดสต๊อก: '+p.name); return; }
  const line = cart.find(c => c.id === p.id);
  if(line){
    if(!isService && line.qty >= p.stock){ toast('สินค้าคงเหลือไม่พอ'); return; }
    line.qty++;
  } else {
    cart.push({...p, is_service: isService, stock: isService ? 999999 : p.stock, qty:1});
  }
  renderCart();
}

function changeQty(id, delta){
  const line = cart.find(c => c.id === id);
  if(!line) return;
  line.qty += delta;
  if(line.qty <= 0) cart = cart.filter(c => c.id !== id);
  else if(!line.is_service && line.qty > line.stock){ line.qty = line.stock; toast('เกินจำนวนคงเหลือ'); }
  renderCart();
}

function setQty(id, val){
  const line = cart.find(c => c.id === id);
  if(!line) return;
  let v = parseInt(val,10); if(isNaN(v)||v<1) v=1;
  if(!line.is_service && v > line.stock){ v = line.stock; toast('เกินจำนวนคงเหลือ'); }
  line.qty = v;
  renderCart();
}

function removeFromCart(id){ cart = cart.filter(c => c.id !== id); renderCart(); }
function clearCart(){ cart=[]; renderCart(); }

function cartTotal(){
  return cart.reduce((a,c)=>a + c.price*c.qty, 0);
}

function renderCart(){
  const wrap = document.getElementById('cartItems');
  const countEl = document.getElementById('cartCount');
  if(countEl) countEl.textContent = cart.length + ' รายการ';

  if(!cart.length){
    wrap.innerHTML = `<div class="cart-empty">
      <div class="cart-empty-icon">🛒</div>
      <div>ยังไม่มีสินค้าในรายการ</div>
      <div class="helptext">แตะสินค้าด้านซ้าย หรือสแกนบาร์โค้ด</div>
    </div>`;
  } else {
    wrap.innerHTML = cart.map(c => `
      <div class="citem">
        ${productThumb(c,'ci-thumb')}
        <div class="ci-info">
          <div class="ci-nm">${escHtml(c.name)}${c.is_service ? ' <span class="badge ok" style="font-size:10px;vertical-align:middle;">บริการ</span>' : ''}</div>
          <div class="ci-sub">${money(c.price)} / ${escHtml(c.unit||'ชิ้น')}</div>
          <div class="ci-row">
            <div class="ci-line">${money(c.price * c.qty)}</div>
            <div class="ci-qty">
              <button class="qtybtn" type="button" onclick="changeQty(${c.id},-1)" aria-label="ลด">−</button>
              <input class="qtynum" value="${c.qty}" onchange="setQty(${c.id}, this.value)" inputmode="numeric">
              <button class="qtybtn" type="button" onclick="changeQty(${c.id},1)" aria-label="เพิ่ม">+</button>
              <button class="qtybtn qtybtn-del" type="button" onclick="removeFromCart(${c.id})" aria-label="ลบ">×</button>
            </div>
          </div>
        </div>
      </div>`).join('');
  }
  const t = cartTotal();
  const vatRate = Number(VAT_RATE) || 0;
  const net = vatRate > 0 ? Math.round((t / (1 + vatRate / 100)) * 100) / 100 : t;
  const vat = Math.round((t - net) * 100) / 100;
  document.getElementById('cartTotals').innerHTML = `
    <div class="totline"><span>รวม ${cart.length} รายการ</span><span class="mono">${money(t)}</span></div>
    ${vatRate > 0 ? `
      <div class="totline tot-vat"><span>ก่อน VAT</span><span class="mono">${money(net)}</span></div>
      <div class="totline tot-vat"><span>VAT ${vatRate}%</span><span class="mono">${money(vat)}</span></div>
    ` : ''}
    <div class="totline grand"><span>ยอดชำระ</span><span class="mono">${money(t)}</span></div>`;
  document.getElementById('payBtn').disabled = cart.length === 0;
}

function filterPos(){
  const q = (document.getElementById('posSearch').value||'').toLowerCase();
  const cat = document.getElementById('posCatFilter').value;
  document.querySelectorAll('#posGrid .pcard').forEach(card => {
    const hay = (card.dataset.search || ((card.dataset.name||'') + ' ' + (card.dataset.barcode||''))).toLowerCase();
    const matchQ = !q || hay.includes(q);
    const matchC = !cat || card.dataset.cat === cat;
    card.style.display = (matchQ && matchC) ? '' : 'none';
  });
}

/* ---- PromptPay EMV QR ---- */
function ppTlv(id, value){
  const len = String(value.length).padStart(2,'0');
  return id + len + value;
}
function ppCrc16(data){
  let crc = 0xFFFF;
  for(let i=0;i<data.length;i++){
    crc ^= data.charCodeAt(i) << 8;
    for(let j=0;j<8;j++){
      crc = (crc & 0x8000) ? ((crc << 1) ^ 0x1021) & 0xFFFF : (crc << 1) & 0xFFFF;
    }
  }
  return crc.toString(16).toUpperCase().padStart(4,'0');
}
function promptPayPayload(id, amount){
  let target = String(id||'').replace(/\D+/g,'');
  if(!target) return '';
  if(target.length===10 && target.startsWith('0')) target = '0066' + target.slice(1);
  else if(target.length===11 && target.startsWith('66')) target = '00' + target;
  const merchant = ppTlv('00','A000000677010111') + ppTlv('01', target);
  return buildPromptPay(merchant, amount);
}
function buildPromptPay(merchant, amount){
  const hasAmount = Number(amount) > 0;
  let payload = ppTlv('00','01')
    + ppTlv('01', hasAmount ? '12' : '11')
    + ppTlv('29', merchant)
    + ppTlv('53','764');
  if(hasAmount) payload += ppTlv('54', Number(amount).toFixed(2));
  payload += ppTlv('58','TH');
  payload += '6304';
  return payload + ppCrc16(payload);
}
function renderQr(el, text){
  if(!el || !window.QRCode) return;
  const payload = typeof text === 'string' ? text : (text ? JSON.stringify(text) : '');
  if(!payload) return;
  el.innerHTML = '';
  el.style.display = 'flex';
  el.style.justifyContent = 'center';
  el.style.alignItems = 'center';
  el.style.minHeight = '160px';
  try {
    new QRCode(el, {
      text: payload,
      width: 160,
      height: 160,
      colorDark: '#000000',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  } catch (err) {
    el.innerHTML = `<div class="helptext" style="color:#C1443C;">สร้าง QR ไม่สำเร็จ</div>`;
  }
}
function promptPayIdForAccount(acc){
  // ห้าม fallback ไปใช้เบอร์พร้อมเพย์ของบัญชีอื่น — ไม่งั้น QR อาจโอนเงินเข้าคนละบัญชีกับที่แสดงบนจอ
  if(!acc) return '';
  return acc.promptpay_id || '';
}
function qrPayloadForAccount(acc, total){
  const ppId = promptPayIdForAccount(acc);
  return ppId ? promptPayPayload(ppId, total) : '';
}

function setPayMethod(method, total){
  payMethod = method;
  document.querySelectorAll('.pay-tab').forEach(btn => {
    const active = btn.dataset.method === method;
    btn.classList.toggle('active', active);
    btn.classList.toggle('btn-primary', active);
    btn.classList.toggle('btn-outline', !active);
  });
  const cashBox = document.getElementById('cashPayBox');
  const transferBox = document.getElementById('transferPayBox');
  if(cashBox) cashBox.style.display = method === 'cash' ? '' : 'none';
  if(transferBox) transferBox.style.display = method === 'transfer' ? '' : 'none';
  if(method === 'transfer'){
    renderTransferAccount(total);
    const paid = document.getElementById('paidInput');
    if(paid) paid.value = total.toFixed(2);
    updateChange(total);
  }
}

function onAccountChange(total){
  const sel = document.getElementById('payAccountSelect');
  selectedAccountId = sel ? Number(sel.value) : selectedAccountId;
  renderTransferAccount(total);
}

function renderTransferAccount(total){
  const acc = getSelectedAccount();
  const detail = document.getElementById('transferDetail');
  if(!detail) return;

  if(!acc){
    detail.innerHTML = `<div class="empty" style="padding:16px;">ยังไม่มีบัญชีรับเงินที่เปิดใช้<br><small>ไปตั้งค่าที่เมนู บัญชีรับเงิน</small></div>`;
    return;
  }

  if(acc.type === 'promptpay'){
    const qrPayload = promptPayPayload(acc.promptpay_id, total);
    detail.innerHTML = `
      <div class="helptext" style="margin-bottom:8px;">ให้ลูกค้าสแกน QR พร้อมเพย์ · ${escHtml(acc.label)}</div>
      <div id="promptPayQr" style="display:inline-block;padding:10px;background:#fff;border:1px solid var(--line);border-radius:10px;"></div>
      <div class="mono" style="margin-top:10px;font-weight:700;">พร้อมเพย์ ${escHtml(acc.promptpay_id)}</div>
      <div class="helptext">ยอด ${money(total)} · โอนแล้วกดยืนยันด้านล่าง</div>`;
    renderQr(document.getElementById('promptPayQr'), qrPayload);
  } else {
    const ppId = promptPayIdForAccount(acc);
    const qrPayload = ppId ? promptPayPayload(ppId, total) : '';
    detail.innerHTML = `
      <div class="helptext" style="margin-bottom:8px;">สแกน QR พร้อมเพย์เพื่อโอน · แสดงรายละเอียดบัญชีด้านล่าง</div>
      ${qrPayload ? `
        <div id="bankPayQr" style="display:inline-block;padding:10px;background:#fff;border:1px solid var(--line);border-radius:10px;"></div>
        <div class="mono" style="margin-top:10px;font-weight:700;">พร้อมเพย์ ${escHtml(ppId)}</div>
      ` : `<div class="helptext" style="color:#C1443C;margin-bottom:10px;">ยังไม่มีเบอร์พร้อมเพย์สำหรับสร้าง QR — ไปแก้ที่เมนูบัญชีรับเงิน</div>`}
      <div style="padding:14px;background:#FBF9F4;border:1px solid var(--line);border-radius:10px;text-align:left;margin-top:10px;">
        <div style="font-weight:700;margin-bottom:6px;">${escHtml(acc.bank_name || 'ธนาคาร')}</div>
        <div>ชื่อบัญชี: <strong>${escHtml(acc.bank_account_name || '—')}</strong></div>
        <div class="mono" style="font-size:18px;margin-top:6px;letter-spacing:.04em;">${escHtml(acc.bank_account_no)}</div>
        <div class="helptext" style="margin-top:8px;">ยอดโอน ${money(total)} · โอนแล้วกดยืนยันด้านล่าง</div>
      </div>`;
    renderQr(document.getElementById('bankPayQr'), qrPayload);
  }
}

function openCheckout(){
  const total = cartTotal();
  payMethod = 'cash';
  selectedAccountId = DEFAULT_ACCOUNT_ID || (PAYMENT_ACCOUNTS[0] && PAYMENT_ACCOUNTS[0].id) || null;

  const accountOptions = PAYMENT_ACCOUNTS.length
    ? PAYMENT_ACCOUNTS.map(a => `<option value="${a.id}" ${a.id===selectedAccountId?'selected':''}>${escHtml(a.label)}${a.is_default?' (Default)':''}</option>`).join('')
    : `<option value="">— ยังไม่มีบัญชี —</option>`;

  openModal(`
    <div class="mh"><h3>ชำระเงิน</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>
    <div class="mb">
      <div class="totline grand" style="border:none;padding:0 0 14px;"><span>ยอดชำระ</span><span class="mono">${money(total)}</span></div>
      <div class="pay-tabs" style="display:flex;gap:8px;margin-bottom:14px;">
        <button type="button" class="btn pay-tab active btn-primary" data-method="cash" style="flex:1" onclick="setPayMethod('cash', ${total})">เงินสด</button>
        <button type="button" class="btn pay-tab btn-outline" data-method="transfer" style="flex:1" onclick="setPayMethod('transfer', ${total})">โอนเงิน</button>
      </div>
      <div id="cashPayBox">
        <div class="field"><label>รับเงินมา (บาท)</label>
          <input id="paidInput" type="number" step="0.01" placeholder="0.00" oninput="updateChange(${total})" autofocus>
        </div>
        <div id="changeBox" class="change-box">
          <div class="change-label" id="changeLabel">เงินทอน</div>
          <div class="change-val mono" id="changeVal">฿0</div>
        </div>
      </div>
      <div id="transferPayBox" style="display:none;text-align:center;">
        <div class="field" style="text-align:left;">
          <label>บัญชีรับเงิน</label>
          <select id="payAccountSelect" onchange="onAccountChange(${total})">${accountOptions}</select>
        </div>
        <div id="transferDetail" style="margin-top:10px;"></div>
      </div>
      <details style="margin-top:14px;">
        <summary style="cursor:pointer;font-weight:600;font-size:13.5px;">ข้อมูลลูกค้าสำหรับใบกำกับภาษี (ไม่บังคับ)</summary>
        <div style="margin-top:10px;">
          <div class="field"><label>ชื่อลูกค้า / บริษัท</label><input id="custName" placeholder="ลูกค้าทั่วไป"></div>
          <div class="row2">
            <div class="field"><label>เลขผู้เสียภาษี</label><input id="custTaxId" class="mono"></div>
            <div class="field"><label>เบอร์โทร</label><input id="custPhone"></div>
          </div>
          <div class="field"><label>ที่อยู่</label><input id="custAddress"></div>
        </div>
      </details>
    </div>
    <div class="mf">
      <button class="btn btn-outline" type="button" onclick="closeModal()">ยกเลิก</button>
      <button class="btn btn-primary" type="button" id="btnConfirmPay" onclick="finalizeSale(${total})">ยืนยันการชำระเงิน</button>
    </div>`);
}

function updateChange(total){
  const paid = parseFloat(document.getElementById('paidInput')?.value)||0;
  const box = document.getElementById('changeBox');
  const label = document.getElementById('changeLabel');
  const el = document.getElementById('changeVal');
  if(!el || !box || !label) return;

  const short = paid > 0 && paid < total;
  const change = Math.max(0, paid - total);
  box.classList.toggle('short', short);
  box.classList.toggle('ok', paid >= total && paid > 0);

  if(short){
    label.textContent = 'จำนวนเงินที่รับมาไม่พอ';
    el.textContent = 'ขาดอีก ' + money(total - paid);
  } else {
    label.textContent = 'เงินทอน';
    el.textContent = money(change);
  }
}

let checkoutInProgress = false;

async function finalizeSale(total){
  if(checkoutInProgress) return; // กันกดซ้ำ/แตะซ้ำเร็วๆ จนยิงออเดอร์ซ้ำ

  let paid = total;
  if(payMethod === 'cash'){
    paid = parseFloat(document.getElementById('paidInput').value)||0;
    if(paid < total){
      updateChange(total);
      await Swal.fire({
        icon: 'warning',
        title: 'จำนวนเงินที่รับมาไม่พอ',
        html: `<div style="font-size:18px;line-height:1.6;margin-top:6px;">
          ยอดชำระ <strong>${money(total)}</strong><br>
          รับมา <strong>${money(paid)}</strong><br>
          <span style="color:#C1443C;font-size:22px;font-weight:700;">ขาดอีก ${money(total - paid)}</span>
        </div>`,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#E4602B',
        heightAuto: false,
      });
      document.getElementById('paidInput')?.focus();
      return;
    }
  } else if(!getSelectedAccount()){
    toast('ยังไม่มีบัญชีรับเงินที่เปิดใช้');
    return;
  }

  if(!cart.length){ toast('ยังไม่มีสินค้าในรายการ'); return; }

  const acc = getSelectedAccount();
  const method = payMethod === 'cash' ? 'cash' : (acc?.type === 'bank' ? 'bank' : 'promptpay');
  const btn = document.getElementById('btnConfirmPay');

  checkoutInProgress = true;
  if(btn){ btn.disabled = true; btn.textContent = 'กำลังบันทึก...'; }

  try {
    const res = await fetch(CHECKOUT_URL, {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.CSRF,'Accept':'application/json'},
      body: JSON.stringify({
        paid,
        payment_method: method,
        payment_account_id: acc?.id || null,
        customer_name: document.getElementById('custName')?.value || null,
        customer_tax_id: document.getElementById('custTaxId')?.value || null,
        customer_address: document.getElementById('custAddress')?.value || null,
        customer_phone: document.getElementById('custPhone')?.value || null,
        items: cart.map(c => ({product_id:c.id, qty:c.qty}))
      })
    });

    let data;
    try {
      data = await res.json();
    } catch (parseErr) {
      toast(res.status === 419 ? 'เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่' : 'เกิดข้อผิดพลาดที่เซิร์ฟเวอร์ กรุณาตรวจสอบรายการขายก่อนลองใหม่');
      return;
    }

    if(!data.ok){ toast(data.message||'ชำระเงินไม่สำเร็จ'); return; }

    cart = [];
    renderCart();
    showReceipt(data.sale);
  } catch (err){
    toast('เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่');
    console.error(err);
  } finally {
    checkoutInProgress = false;
    if(btn){ btn.disabled = false; btn.textContent = 'ยืนยันการชำระเงิน'; }
  }
}

function showReceipt(sale){
  const methodLabels = {cash:'เงินสด', promptpay:'พร้อมเพย์', bank:'โอนธนาคาร'};
  const methodLabel = methodLabels[sale.payment_method] || sale.payment_method;
  const acc = sale.payment_account;
  let transferBlock = '';
  if(sale.payment_method === 'promptpay'){
    const ppId = (acc && acc.promptpay_id) || sale.promptpay_id || '';
    transferBlock = `
      <hr>
      <div style="text-align:center;font-size:11px;">ชำระผ่านพร้อมเพย์ ${escHtml(ppId)}${acc?.label ? ' · '+escHtml(acc.label) : ''}</div>
      <div id="receiptPpQr" style="display:flex;justify-content:center;margin:8px 0;"></div>`;
  } else if(sale.payment_method === 'bank'){
    transferBlock = `
      <hr>
      <div style="text-align:center;font-size:11px;line-height:1.5;">
        โอนเข้า ${escHtml(acc?.bank_name || 'ธนาคาร')}<br>
        ${escHtml(acc?.bank_account_name || '')}<br>
        <span class="mono">${escHtml(acc?.bank_account_no || '')}</span>
      </div>
      <div id="receiptBankQr" style="display:flex;justify-content:center;margin:8px 0;"></div>`;
  } else {
    transferBlock = `
      <hr>
      <div style="text-align:center;font-size:11px;">สแกน QR เพื่อดูใบเสร็จอิเล็กทรอนิกส์</div>
      <div id="qrbox" style="display:flex;justify-content:center;margin:10px 0;min-height:140px;"></div>`;
  }

  openModal(`
    <div class="mh"><h3>ใบเสร็จ</h3><button class="xbtn" type="button" onclick="closeModal(); location.reload();">✕</button></div>
    <div class="mb">
      <div class="receipt" id="receiptPrint">
        <div style="text-align:center;font-weight:700;font-size:14px;">${escHtml(sale.shop_name)}</div>
        <div style="text-align:center;">เลขที่ ${escHtml(sale.receipt_no)}</div>
        <div style="text-align:center;">${escHtml(sale.sold_at)}</div>
        <hr>
        <div class="ritem head"><span>รายการสินค้า</span><span class="qty">จำนวน</span><span class="price">ราคา</span></div>
        ${sale.items.map(it => `
          <div class="ritem">
            <span class="name">${escHtml(it.name)}</span>
            <span class="qty">${escHtml(it.qty)}</span>
            <span class="price">${fmt(it.total)}</span>
          </div>`).join('')}
        <hr>
        <div class="rline" style="font-weight:700;"><span>ยอดรวม</span><span>${money(sale.total)}</span></div>
        ${sale.vat_amount != null ? `
          <div class="rline"><span>ก่อน VAT</span><span>${money(sale.net_amount)}</span></div>
          <div class="rline"><span>VAT ${sale.vat_rate||7}%</span><span>${money(sale.vat_amount)}</span></div>
        ` : ''}
        <div class="rline"><span>ชำระโดย</span><span>${methodLabel}</span></div>
        <div class="rline"><span>รับเงิน</span><span>${money(sale.paid)}</span></div>
        <div class="rline"><span>เงินทอน</span><span>${money(sale.change)}</span></div>
        ${transferBlock}
      </div>
      <div class="no-print" style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap;">
        <button class="btn btn-outline" style="flex:1" type="button" onclick="window.print()">พิมพ์ใบเสร็จ</button>
        ${sale.invoice_url ? `<a class="btn btn-dark" style="flex:1;justify-content:center;" href="${sale.invoice_url}" target="_blank">ใบกำกับภาษี</a>` : ''}
        <button class="btn btn-primary" style="flex:1" type="button" onclick="closeModal(); location.reload();">เสร็จสิ้น / ขายต่อ</button>
      </div>
    </div>`);

  // รอ DOM ของ modal พร้อมก่อนวาด QR
  setTimeout(() => {
    if(!window.QRCode) return;
    if(sale.payment_method === 'promptpay'){
      const ppId = (acc && acc.promptpay_id) || sale.promptpay_id || '';
      renderQr(document.getElementById('receiptPpQr'), promptPayPayload(ppId, sale.total));
    } else if(sale.payment_method === 'bank'){
      const payload = qrPayloadForAccount({
        type: 'bank',
        promptpay_id: acc?.promptpay_id,
        bank_name: acc?.bank_name,
        bank_account_no: acc?.bank_account_no
      }, sale.total);
      renderQr(document.getElementById('receiptBankQr'), payload);
    } else {
      const receiptUrl = sale.receipt_url || sale.qr || sale.invoice_url || '';
      renderQr(document.getElementById('qrbox'), receiptUrl);
    }
  }, 50);
}

document.getElementById('scanInput').addEventListener('keydown', async e => {
  if(e.key !== 'Enter') return;
  const code = e.target.value.trim();
  e.target.value = '';
  if(!code) return;
  try {
    const res = await fetch(BARCODE_URL, {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.CSRF,'Accept':'application/json'},
      body: JSON.stringify({barcode: code})
    });
    const data = await res.json();
    if(!data.ok){ toast(data.message||'ไม่พบสินค้า'); return; }
    const p = data.product;
    addToCart({
      id:p.id,
      name:p.name,
      price:p.sell_price,
      stock:p.is_service ? 999999 : p.stock,
      unit:p.unit,
      image:p.image,
      icon:p.icon,
      color:p.color,
      is_service: !!p.is_service,
    });
  } catch (err) {
    toast('ค้นหาบาร์โค้ดไม่สำเร็จ กรุณาลองใหม่');
    console.error(err);
  }
});

renderCart();
document.getElementById('scanInput').focus();
</script>
<style>
.pay-tab.active{background:var(--orange)!important;color:#fff!important;border-color:var(--orange)!important;}
.change-box{
  margin-top:12px; text-align:center; padding:18px 14px; border-radius:12px;
  background:#F3F0E8; border:1px solid var(--line);
}
.change-box .change-label{
  font-size:14px; font-weight:700; color:var(--steel-500); margin-bottom:6px;
}
.change-box .change-val{
  font-family:'Barlow Condensed',sans-serif; font-size:42px; font-weight:800;
  color:var(--steel-900); line-height:1.1;
}
.change-box.ok{
  background:#E4F3E7; border-color:#b7d9c0;
}
.change-box.ok .change-label{color:var(--ok);}
.change-box.ok .change-val{color:#2d6b3d;}
.change-box.short{
  background:#FBE7E5; border-color:#ecc;
}
.change-box.short .change-label{color:var(--danger); font-size:16px;}
.change-box.short .change-val{color:var(--danger); font-size:36px;}
</style>
@endpush
