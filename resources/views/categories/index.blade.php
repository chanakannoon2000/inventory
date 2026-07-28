@extends('layouts.app')

@section('title', 'หมวดหมู่สินค้า')

@section('content')
<div class="panel">
    <div class="ph">
        <h3>หมวดหมู่สินค้า <span class="badge neutral">{{ $categories->count() }} รายการ</span></h3>
        <button class="btn btn-primary" type="button" onclick="openMaster()">+ เพิ่ม</button>
    </div>
    <div class="pb">
        <p class="helptext" style="margin-bottom:12px;font-size:13px;">ตัวอักษรนำหน้าใช้สร้างบาร์โค้ด เช่น หมวดกระเบื้อง = <strong>A</strong> → บาร์โค้ด <strong>A123456</strong></p>
        <table>
            <thead><tr><th>ชื่อหมวดหมู่</th><th>ตัวอักษรบาร์โค้ด</th><th>ไอคอน</th><th>ใช้งานในสินค้า</th><th></th></tr></thead>
            <tbody>
            @forelse($categories as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td><span class="mono badge info">{{ $item->barcode_prefix ?: '—' }}</span></td>
                    <td><span style="background:{{ $item->color }};padding:4px 8px;border-radius:6px;">{{ $item->icon }}</span></td>
                    <td><span class="badge info">{{ $item->products_count }} SKU</span></td>
                    <td>
                        <button class="btn btn-outline btn-sm" type="button" onclick='openMaster(@json($item))'>แก้ไข</button>
                        <form method="POST" action="{{ route('categories.destroy', $item) }}" style="display:inline;" onsubmit="return confirmDelete(this, 'ยืนยันลบหมวดหมู่นี้?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">ลบ</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty">ยังไม่มีข้อมูล</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const BASE = @json(url('/categories'));
function escAttr(v){
  return String(v == null ? '' : v)
    .replace(/&/g,'&amp;')
    .replace(/"/g,'&quot;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;');
}
function openMaster(item=null){
  const action = item ? `${BASE}/${item.id}` : BASE;
  openModal(`
    <div class="mh"><h3>${item?'แก้ไข':'เพิ่ม'}หมวดหมู่</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>
    <form class="mb" method="POST" action="${action}">
      <input type="hidden" name="_token" value="${window.CSRF}">
      ${item?'<input type="hidden" name="_method" value="PUT">':''}
      <div class="field"><label>ชื่อหมวดหมู่</label><input name="name" required value="${escAttr(item?.name)}"></div>
      <div class="row2">
        <div class="field"><label>ตัวอักษรนำหน้าบาร์โค้ด (A-Z)</label>
          <input name="barcode_prefix" class="mono" maxlength="1" required value="${escAttr(item?.barcode_prefix)}" placeholder="เช่น A" style="text-transform:uppercase;">
        </div>
        <div class="field"><label>ไอคอน</label><input name="icon" value="${escAttr(item?.icon||'📦')}"></div>
      </div>
      <div class="field"><label>สีพื้นหลัง</label><input name="color" value="${escAttr(item?.color||'#E3DFD3')}"></div>
      <div class="mf" style="margin:16px -20px -18px;">
        <button class="btn btn-outline" type="button" onclick="closeModal()">ยกเลิก</button>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </div>
    </form>`);
}
</script>
@endpush
