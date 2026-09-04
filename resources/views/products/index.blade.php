@extends('layouts.app')

@section('title', 'สินค้า')

@section('content')
<div class="panel">
    <div class="ph">
        <h3>สินค้า / บริการ <span class="badge neutral">{{ $products->total() }} รายการ</span></h3>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @if(auth()->user()->canViewCost())
                <form method="POST" action="{{ route('products.toggle-cost') }}">
                    @csrf
                    <button class="btn btn-outline btn-sm" type="submit">
                        {{ $showCost ? 'ซ่อนราคาทุน' : 'แสดงราคาทุน (เจ้าของร้าน)' }}
                    </button>
                </form>
            @endif
            <a class="btn btn-outline btn-sm" href="{{ route('products.export', array_merge(request()->query(), ['format' => 'csv'])) }}">Export CSV</a>
            <a class="btn btn-outline btn-sm" href="{{ route('products.export', array_merge(request()->query(), ['format' => 'excel'])) }}">Export Excel</a>
            <button class="btn btn-primary" type="button" id="btnAddProduct">+ เพิ่มรายการ</button>
        </div>
    </div>
    <div class="pb">
        <form class="searchbar" method="GET">
            <input name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อ/บาร์โค้ด...">
            <select name="type">
                <option value="">ทุกประเภท</option>
                <option value="product" @selected(request('type')==='product')>สินค้า</option>
                <option value="service" @selected(request('type')==='service')>บริการ</option>
            </select>
            <select name="category_id">
                <option value="">ทุกหมวดหมู่</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="stock">
                <option value="">สถานะสต๊อกทั้งหมด</option>
                <option value="low" @selected(request('stock')==='low')>ต่ำกว่าขั้นต่ำ</option>
                <option value="over" @selected(request('stock')==='over')>เกิน Max</option>
            </select>
            <button class="btn btn-dark" type="submit">ค้นหา</button>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                <tr>
                    <th></th><th>รายการ</th><th>บาร์โค้ด</th><th>หมวดหมู่</th><th>หน่วย</th>
                    <th>ทุน {{ $showCost ? '' : '(รหัส)' }}</th><th>ราคาขาย</th><th>คงเหลือ</th><th>Min/Max</th><th>Supplier</th><th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                    <tr>
                        <td>
                            @if($p->imageSrc())
                                <img class="thumb" src="{{ $p->imageSrc() }}" alt="">
                            @else
                                <div class="thumb ph-icon" style="background:{{ $p->placeholderColor() }}">{{ $p->isService() ? '🚚' : $p->placeholderIcon() }}</div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $p->name }}</strong>
                            <div style="margin-top:3px;">
                                @if($p->isService())
                                    <span class="badge ok">บริการ</span>
                                @else
                                    <span class="badge neutral">สินค้า</span>
                                @endif
                            </div>
                            @if($p->productGroup)
                                <div class="helptext" style="margin-top:2px;">
                                    กลุ่ม: {{ $p->productGroup->name }}
                                    @if($p->size_label) · ไซส์ {{ $p->size_label }}@endif
                                </div>
                            @endif
                        </td>
                        <td class="mono">{{ $p->barcode }}</td>
                        <td>{{ $p->category?->name ?? '—' }}</td>
                        <td>{{ $p->unit?->name ?? '—' }}</td>
                        <td class="mono tag-cipher">
                            @if(auth()->user()->canViewCost() && $showCost)
                                {{ ($money)($p->cost_price) }}
                            @else
                                {{ ($costCode)($p->cost_price) }}
                            @endif
                        </td>
                        <td class="mono">{{ ($money)($p->sell_price) }}</td>
                        <td>
                            @if($p->isService())
                                <span class="helptext">ไม่ตัดสต๊อก</span>
                            @else
                                <span class="mono">{{ ($fmt)($p->stock) }}</span>
                                @php $pct = $p->max_stock > 0 ? min(100, ($p->stock / $p->max_stock) * 100) : 0; @endphp
                                <div class="stockbar"><i style="width:{{ $pct }}%; background:{{ $p->isLowStock() ? '#C1443C' : ($p->isOverStock() ? '#CC9A1E' : '#3F8F55') }}"></i></div>
                            @endif
                        </td>
                        <td class="mono">
                            @if($p->isService())
                                —
                            @else
                                {{ ($fmt)($p->min_stock) }} / {{ ($fmt)($p->max_stock) }}
                            @endif
                        </td>
                        <td>{{ $p->supplier?->name ?? '—' }}</td>
                        <td>
                            @if($p->isService())
                                <span class="badge ok">บริการ</span>
                            @elseif($p->isLowStock())
                                <span class="badge danger">ต่ำกว่า Min</span>
                            @elseif($p->isOverStock())
                                <span class="badge warn">เกิน Max</span>
                            @else
                                <span class="badge ok">ปกติ</span>
                            @endif
                            <br>
                            <button class="btn btn-outline btn-sm btn-edit-product" style="margin-top:5px;" type="button"
                                data-id="{{ $p->id }}">แก้ไข</button>
                            <form method="POST" action="{{ route('products.destroy', $p) }}" style="display:inline;" onsubmit="return confirmDelete(this, 'ยืนยันลบรายการนี้?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11"><div class="empty">ไม่พบรายการตามเงื่อนไข</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $products->links('pagination.simple') }}
    </div>
</div>

{{-- hidden templates for selects --}}
<template id="catOptions">
    @foreach($categories as $c)<option value="{{ $c->id }}" data-icon="{{ $c->icon }}" data-color="{{ $c->color }}">{{ $c->name }}</option>@endforeach
</template>
<template id="groupOptions">
    <option value="">— ไม่ระบุ (สินค้าเดี่ยว) —</option>
    @foreach($productGroups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
</template>
<template id="unitOptions">
    @foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
</template>
<template id="supOptions">
    <option value="">— ไม่ระบุ —</option>
    @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
</template>
@endsection

@push('vendor_scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
@endpush

@push('scripts')
@php
    $canViewCostJs = auth()->user()->canViewCost();
    $productMap = [];
    foreach ($products as $p) {
        $productMap[(string) $p->id] = [
            'id' => $p->id,
            'name' => $p->name,
            'type' => $p->type ?: 'product',
            'product_group_id' => $p->product_group_id,
            'size_label' => $p->size_label,
            'barcode' => $p->barcode,
            'category_id' => $p->category_id,
            'unit_id' => $p->unit_id,
            'supplier_id' => $p->supplier_id,
            // ไม่ส่งราคาทุนจริงให้พนักงานที่ไม่มีสิทธิ์ดู ป้องกันเปิด DevTools แล้วเห็นต้นทุน
            'cost_price' => $canViewCostJs ? $p->cost_price : null,
            'sell_price' => $p->sell_price,
            'stock' => $p->stock,
            'min_stock' => $p->min_stock,
            'max_stock' => $p->max_stock,
            'image_url' => $p->image_url,
        ];
    }
@endphp
<script>
window.PRODUCTS = @json($productMap);
window.STORE_URL = @json(url('/products'));
window.BARCODE_URL = @json(url('/products/barcode-preview'));
window.CIPHER_URL = @json(url('/products/cipher-preview'));
window.INDEX_URL = @json(url('/products'));
window.CAN_VIEW_COST = @json((bool) auth()->user()->canViewCost());
var resizedImageBlob = null;

function fallbackBarcode(prefix){
  prefix = prefix || '';
  return prefix + String(Math.floor(Math.random() * 1e6)).padStart(6, '0');
}

async function genBarcode(categoryId){
  try{
    var cat = categoryId;
    if(cat == null || cat === undefined){
      var catEl = document.getElementById('f_cat');
      cat = catEl ? catEl.value : '';
    }
    var url = new URL(window.BARCODE_URL, window.location.origin);
    if(cat) url.searchParams.set('category_id', cat);
    var res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
    if(!res.ok) throw new Error('barcode http '+res.status);
    var data = await res.json();
    if(!data.barcode) throw new Error('empty barcode');
    return data.barcode;
  }catch(err){
    return fallbackBarcode();
  }
}

async function refreshBarcode(){
  var input = document.getElementById('f_barcode');
  if(!input) return;
  input.value = await genBarcode();
  renderProductBarcode();
}

function onCategoryChange(){
  var form = document.getElementById('productForm');
  if(form && form.dataset.editing === '1') return;
  refreshBarcode();
}

function renderProductBarcode(){
  var input = document.getElementById('f_barcode');
  var svg = document.getElementById('barcodePreviewSvg');
  var wrap = document.getElementById('barcodePreviewWrap');
  var btn = document.getElementById('btnDownloadBarcode');
  var codeText = document.getElementById('barcodePreviewCode');
  if(!input || !svg || !wrap) return;

  var code = String(input.value || '').trim();
  if(!code){
    wrap.style.display = 'none';
    if(btn) btn.disabled = true;
    if(codeText) codeText.textContent = '';
    svg.innerHTML = '';
    return;
  }

  try{
    if(typeof JsBarcode !== 'function'){
      throw new Error('JsBarcode not loaded');
    }
    svg.innerHTML = '';
    JsBarcode(svg, code, {
      format: 'CODE128',
      width: 2,
      height: 72,
      displayValue: true,
      fontSize: 14,
      margin: 8,
      background: '#ffffff',
      lineColor: '#111111',
    });
    wrap.style.display = 'block';
    if(btn) btn.disabled = false;
    if(codeText) codeText.textContent = code;
  }catch(err){
    wrap.style.display = 'none';
    if(btn) btn.disabled = true;
    if(codeText) codeText.textContent = '';
    console.warn('barcode render failed', err);
  }
}

function downloadProductBarcode(){
  var input = document.getElementById('f_barcode');
  var svg = document.getElementById('barcodePreviewSvg');
  if(!input || !svg || !svg.childNodes.length){
    toast('ยังไม่มีบาร์โค้ดให้ดาวน์โหลด');
    return;
  }
  var code = String(input.value || '').trim() || 'barcode';
  var nameInput = document.querySelector('#productForm input[name=name]');
  var productName = nameInput ? String(nameInput.value || '').trim() : '';
  var xml = new XMLSerializer().serializeToString(svg);
  var svgBlob = new Blob([xml], { type: 'image/svg+xml;charset=utf-8' });
  var url = URL.createObjectURL(svgBlob);
  var img = new Image();
  img.onload = function(){
    var pad = 24;
    var canvas = document.createElement('canvas');
    canvas.width = img.width + pad * 2;
    canvas.height = img.height + pad * 2 + (productName ? 28 : 0);
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    if(productName){
      ctx.fillStyle = '#222';
      ctx.font = '600 16px "IBM Plex Sans Thai", sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(productName, canvas.width / 2, 22);
    }
    ctx.drawImage(img, pad, pad + (productName ? 20 : 0));
    canvas.toBlob(function(blob){
      if(!blob){ toast('สร้างไฟล์บาร์โค้ดไม่สำเร็จ'); return; }
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'barcode-'+code+'.png';
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(a.href);
      URL.revokeObjectURL(url);
      toast('ดาวน์โหลดบาร์โค้ดแล้ว');
    }, 'image/png');
  };
  img.onerror = function(){
    URL.revokeObjectURL(url);
    toast('สร้างไฟล์บาร์โค้ดไม่สำเร็จ');
  };
  img.src = url;
}

function escAttr(v){
  return String(v == null ? '' : v)
    .replace(/&/g,'&amp;')
    .replace(/"/g,'&quot;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;');
}

function setImagePreview(src){
  var wrap = document.getElementById('imgPreviewWrap');
  if(!wrap) return;
  if(src){
    wrap.innerHTML = '<img class="thumb" style="width:180px;height:180px;object-fit:cover;border-radius:12px;" src="'+escAttr(src)+'" alt="preview">';
  } else {
    wrap.innerHTML = '<div class="thumb ph-icon" style="width:180px;height:180px;font-size:56px;background:#E3DFD3;border-radius:12px;">📦</div>';
  }
}

function resizeImageFile(file, maxW, quality){
  maxW = maxW || 800;
  quality = quality || 0.82;
  return new Promise(function(resolve, reject){
    if(!file || !file.type || file.type.indexOf('image/') !== 0){
      reject(new Error('กรุณาเลือกไฟล์รูปภาพ'));
      return;
    }
    var reader = new FileReader();
    reader.onerror = function(){ reject(new Error('อ่านไฟล์ไม่สำเร็จ')); };
    reader.onload = function(){
      var img = new Image();
      img.onerror = function(){ reject(new Error('เปิดรูปภาพไม่สำเร็จ')); };
      img.onload = function(){
        var scale = Math.min(1, maxW / img.width);
        var canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(img.width * scale));
        canvas.height = Math.max(1, Math.round(img.height * scale));
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(function(blob){
          if(!blob){ reject(new Error('ย่อรูปไม่สำเร็จ')); return; }
          resolve({ blob: blob, preview: canvas.toDataURL('image/jpeg', quality) });
        }, 'image/jpeg', quality);
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(file);
  });
}

async function handleImageSelect(event){
  var file = event.target.files && event.target.files[0];
  resizedImageBlob = null;
  if(!file){ return; }
  try{
    var result = await resizeImageFile(file);
    resizedImageBlob = result.blob;
    setImagePreview(result.preview);
    var urlInput = document.querySelector('#productForm input[name=image_url_link]');
    if(urlInput) urlInput.value = '';
    toast('ย่อรูปพร้อมแสดงตัวอย่างแล้ว');
  }catch(err){
    resizedImageBlob = null;
    toast(err.message || 'เลือกรูปไม่สำเร็จ');
    event.target.value = '';
  }
}

function handleImageUrlInput(value){
  var url = (value || '').trim();
  if(url){
    resizedImageBlob = null;
    var fileInput = document.querySelector('#productForm input[name=image]');
    if(fileInput) fileInput.value = '';
    setImagePreview(url);
  }
}

function openProductForm(p){
  p = p || null;
  resizedImageBlob = null;
  var catEl = document.getElementById('catOptions');
  var groupEl = document.getElementById('groupOptions');
  var unitEl = document.getElementById('unitOptions');
  var supEl = document.getElementById('supOptions');
  var catHtml = '<option value="">— ไม่ระบุ —</option>' + (catEl ? catEl.innerHTML : '');
  var groupHtml = groupEl ? groupEl.innerHTML : '<option value="">— ไม่ระบุ (สินค้าเดี่ยว) —</option>';
  var unitHtml = '<option value="">— ไม่ระบุ —</option>' + (unitEl ? unitEl.innerHTML : '');
  var supHtml = supEl ? supEl.innerHTML : '';
  var action = p ? (window.STORE_URL + '/' + p.id) : window.STORE_URL;
  var img = '';
  if(p && p.image_url){
    img = (String(p.image_url).indexOf('http') === 0) ? p.image_url : ('/storage/' + p.image_url);
  }
  var isService = p && p.type === 'service';
  var typeProductSel = !isService ? ' selected' : '';
  var typeServiceSel = isService ? ' selected' : '';
  var stockRowStyle = isService ? 'display:none;' : '';
  var groupRowStyle = isService ? 'display:none;' : '';
  var costField = window.CAN_VIEW_COST
    ? '<div class="field"><label>ราคาทุน (บาท)</label><input name="cost_price" id="f_cost" type="number" step="0.01" value="'+(p && p.cost_price != null ? p.cost_price : '')+'"></div>'
    : '<input type="hidden" name="cost_price" id="f_cost" value="'+(p && p.cost_price != null ? p.cost_price : 0)+'">';
  var barcode = escAttr(p && p.barcode ? p.barcode : '');
  var imgPreview = img
    ? '<img class="thumb" style="width:180px;height:180px;object-fit:cover;border-radius:12px;" src="'+escAttr(img)+'" alt="preview">'
    : '<div class="thumb ph-icon" style="width:180px;height:180px;font-size:56px;background:#E3DFD3;border-radius:12px;">'+(isService ? '🚚' : '📦')+'</div>';
  var clearImage = p
    ? '<label style="margin-top:8px;display:flex;gap:6px;align-items:center;"><input type="checkbox" name="clear_image" value="1" style="width:auto;"> ลบรูป</label>'
    : '';
  var methodField = p ? '<input type="hidden" name="_method" value="PUT">' : '';
  var cipherHelp = window.CAN_VIEW_COST
    ? '<div class="helptext">รหัสลับต้นทุน: <span class="mono tag-cipher" id="codePreview">----</span></div>'
    : '';
  var imgUrlVal = (p && p.image_url && String(p.image_url).indexOf('http') === 0) ? escAttr(p.image_url) : '';

  openModal(
    '<div class="mh"><h3>'+(p ? (isService ? 'แก้ไขบริการ' : 'แก้ไขสินค้า') : 'เพิ่มสินค้า / บริการ')+'</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>'+
    '<form class="mb" method="POST" action="'+action+'" enctype="multipart/form-data" id="productForm">'+
      '<input type="hidden" name="_token" value="'+window.CSRF+'">'+
      methodField+
      '<div class="field"><label>รูปภาพ</label>'+
        '<div style="display:flex;gap:12px;align-items:flex-start;">'+
          '<div id="imgPreviewWrap">'+imgPreview+'</div>'+
          '<div style="flex:1;">'+
            '<input type="file" name="image" id="f_image" accept="image/jpeg,image/png,image/webp,image/gif" onchange="handleImageSelect(event)">'+
            '<div class="field" style="margin-top:8px;margin-bottom:0;">'+
              '<label>หรือวางลิงก์ URL รูปภาพ</label>'+
              '<input name="image_url_link" placeholder="https://..." value="'+imgUrlVal+'" oninput="handleImageUrlInput(this.value)">'+
            '</div>'+
            clearImage+
            '<div class="droptxt helptext">เลือกรูปแล้วจะแสดงตัวอย่างทันที และระบบจะย่อขนาดก่อนบันทึก</div>'+
          '</div>'+
        '</div>'+
      '</div>'+
      '<div class="field"><label>ประเภท</label>'+
        '<select name="type" id="f_type" onchange="onProductTypeChange()">'+
          '<option value="product"'+typeProductSel+'>สินค้า (ตัดสต๊อก)</option>'+
          '<option value="service"'+typeServiceSel+'>บริการ (เช่น ค่าส่ง ไม่ตัดสต๊อก)</option>'+
        '</select>'+
      '</div>'+
      '<div class="field"><label>ชื่อ</label><input name="name" required value="'+escAttr(p && p.name ? p.name : '')+'" placeholder="เช่น ค่าจัดส่งสินค้า / ท่อ PVC 1 นิ้ว"></div>'+
      '<div class="row2" id="groupFieldsRow" style="'+groupRowStyle+'">'+
        '<div class="field"><label>กลุ่มสินค้า (หลายไซส์)</label><select name="product_group_id" id="f_group">'+groupHtml+'</select></div>'+
        '<div class="field"><label>ไซส์ / ขนาด</label><input name="size_label" value="'+escAttr(p && p.size_label ? p.size_label : '')+'" placeholder="เช่น 4 หุน, 1 นิ้ว, 2 นิ้ว"></div>'+
      '</div>'+
      '<div class="helptext" id="groupHelpText" style="margin-top:-6px;margin-bottom:10px;'+groupRowStyle+'">สร้างกลุ่มที่เมนู <strong>กลุ่มสินค้า</strong> ก่อน แล้วเลือกที่นี่ + ใส่ไซส์ — POS จะรวมเป็นการ์ดเดียวให้เลือกขนาด · <a href="'+@json(url('/product-groups'))+'" target="_blank">ไปจัดการกลุ่ม</a></div>'+
      '<div class="row2">'+
        '<div class="field"><label>บาร์โค้ด</label>'+
          '<div style="display:flex;gap:6px;">'+
            '<input name="barcode" class="mono" id="f_barcode" value="'+barcode+'" placeholder="กำลังสุ่ม..." oninput="renderProductBarcode()">'+
            '<button type="button" class="btn btn-outline btn-sm" onclick="refreshBarcode()">สุ่ม</button>'+
          '</div>'+
        '</div>'+
        '<div class="field"><label>หมวดหมู่</label><select name="category_id" id="f_cat" onchange="onCategoryChange()">'+catHtml+'</select></div>'+
      '</div>'+
      '<div class="row2">'+
        '<div class="field"><label>หน่วยนับ</label><select name="unit_id">'+unitHtml+'</select></div>'+
        '<div class="field"><label>ผู้จำหน่าย</label><select name="supplier_id">'+supHtml+'</select></div>'+
      '</div>'+
      '<div class="row2">'+
        costField+
        '<div class="field"><label>ราคาขาย (บาท)</label><input name="sell_price" type="number" step="0.01" value="'+(p && p.sell_price != null ? p.sell_price : '')+'"></div>'+
      '</div>'+
      '<div class="row3" id="stockFieldsRow" style="'+stockRowStyle+'">'+
        '<div class="field"><label>คงเหลือ</label><input name="stock" type="number" step="0.01" value="'+(p && p.stock != null ? p.stock : 0)+'"></div>'+
        '<div class="field"><label>Min</label><input name="min_stock" type="number" step="0.01" value="'+(p && p.min_stock != null ? p.min_stock : 5)+'"></div>'+
        '<div class="field"><label>Max</label><input name="max_stock" type="number" step="0.01" value="'+(p && p.max_stock != null ? p.max_stock : 100)+'"></div>'+
      '</div>'+
      '<div class="helptext" id="serviceStockHint" style="'+(isService ? '' : 'display:none;')+'margin-bottom:10px;">บริการไม่ตัดสต๊อก — ใส่ในบิลขายคู่กับสินค้าได้เลย (เช่น ค่าส่ง)</div>'+
      cipherHelp+
      '<div id="barcodePreviewWrap" class="barcode-preview-wrap" style="display:none;margin-top:14px;">'+
        '<div class="helptext" style="margin-bottom:8px;">ตัวอย่างบาร์โค้ด</div>'+
        '<svg id="barcodePreviewSvg" class="barcode-preview-svg"></svg>'+
        '<div class="barcode-preview-meta">'+
          '<span class="mono" id="barcodePreviewCode"></span>'+
          '<button type="button" class="btn btn-outline btn-sm" id="btnDownloadBarcode" onclick="downloadProductBarcode()" disabled>ดาวน์โหลดบาร์โค้ด</button>'+
        '</div>'+
      '</div>'+
      '<div class="mf" style="margin:16px -20px -18px;padding:14px 20px;">'+
        '<button class="btn btn-outline" type="button" onclick="closeModal()">ยกเลิก</button>'+
        '<button class="btn btn-primary" type="submit">บันทึก</button>'+
      '</div>'+
    '</form>'
  );

  var form = document.getElementById('productForm');
  if(!form){
    toast('เปิดฟอร์มไม่สำเร็จ');
    return;
  }
  if(p){
    form.dataset.editing = '1';
    form.querySelector('select[name=category_id]').value = p.category_id || '';
    form.querySelector('select[name=product_group_id]').value = p.product_group_id || '';
    form.querySelector('select[name=unit_id]').value = p.unit_id || '';
    form.querySelector('select[name=supplier_id]').value = p.supplier_id || '';
    renderProductBarcode();
  } else {
    form.dataset.editing = '0';
    refreshBarcode();
  }

  form.addEventListener('submit', async function(e){
    e.preventDefault();
    var btn = form.querySelector('button[type=submit]');
    if(btn){ btn.disabled = true; btn.textContent = 'กำลังบันทึก...'; }

    try{
      var fd = new FormData(form);
      var urlLink = (fd.get('image_url_link') || '').toString().trim();
      if(!urlLink){ fd.delete('image_url_link'); }

      var rawImage = fd.get('image');
      if(!(rawImage instanceof File) || !rawImage.size){
        fd.delete('image');
      }

      if(resizedImageBlob){
        fd.delete('image');
        fd.append('image', resizedImageBlob, 'product.jpg');
      }

      var res = await fetch(action, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': window.CSRF,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: fd,
      });

      if(res.redirected){
        window.location.href = res.url;
        return;
      }

      var data = await res.json().catch(function(){ return {}; });
      if(res.ok){
        window.location.href = window.INDEX_URL;
        return;
      }

      if(res.status === 422 && data.errors){
        var first = Object.values(data.errors)[0];
        toast(Array.isArray(first) ? first[0] : 'ข้อมูลไม่ถูกต้อง');
      } else if(res.status === 419){
        toast('เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่');
      } else {
        toast(data.message || 'บันทึกไม่สำเร็จ');
      }
    } catch(err){
      toast('บันทึกไม่สำเร็จ กรุณาลองใหม่');
      console.error(err);
    } finally {
      if(btn){ btn.disabled = false; btn.textContent = 'บันทึก'; }
    }
  });

  if(window.CAN_VIEW_COST){
    var costInput = document.getElementById('f_cost');
    var updateCipher = async function(){
      try{
        var res = await fetch(window.CIPHER_URL + '?cost=' + encodeURIComponent(costInput.value||0));
        var data = await res.json();
        var preview = document.getElementById('codePreview');
        if(preview) preview.textContent = data.code;
      }catch(e){}
    };
    costInput.addEventListener('input', updateCipher);
    updateCipher();
  }
}

window.openProductForm = openProductForm;
window.handleImageSelect = handleImageSelect;
window.handleImageUrlInput = handleImageUrlInput;
window.refreshBarcode = refreshBarcode;
window.onCategoryChange = onCategoryChange;
window.onProductTypeChange = onProductTypeChange;
window.renderProductBarcode = renderProductBarcode;
window.downloadProductBarcode = downloadProductBarcode;

function onProductTypeChange(){
  var typeEl = document.getElementById('f_type');
  var isService = typeEl && typeEl.value === 'service';
  var stockRow = document.getElementById('stockFieldsRow');
  var groupRow = document.getElementById('groupFieldsRow');
  var groupHelp = document.getElementById('groupHelpText');
  var hint = document.getElementById('serviceStockHint');
  if(stockRow) stockRow.style.display = isService ? 'none' : '';
  if(groupRow) groupRow.style.display = isService ? 'none' : '';
  if(groupHelp) groupHelp.style.display = isService ? 'none' : '';
  if(hint) hint.style.display = isService ? '' : 'none';
  if(isService){
    var group = document.getElementById('f_group');
    if(group) group.value = '';
  }
}

(function bindProductButtons(){
  var addBtn = document.getElementById('btnAddProduct');
  if(addBtn){
    addBtn.addEventListener('click', function(){ openProductForm(null); });
  }
  document.querySelectorAll('.btn-edit-product').forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = btn.getAttribute('data-id');
      var product = window.PRODUCTS && (window.PRODUCTS[id] || window.PRODUCTS[String(id)]);
      if(!product){
        toast('ไม่พบข้อมูลสินค้า');
        return;
      }
      openProductForm(product);
    });
  });
})();
</script>
@endpush
