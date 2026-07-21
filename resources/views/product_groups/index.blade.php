@extends('layouts.app')

@section('title', 'กลุ่มสินค้า')

@section('content')
<div class="panel">
    <div class="ph">
        <h3>กลุ่มสินค้า (หลายไซส์) <span class="badge neutral">{{ $groups->count() }} รายการ</span></h3>
        <button class="btn btn-primary" type="button" onclick="openMaster()">+ เพิ่มกลุ่ม</button>
    </div>
    <div class="pb">
        <p class="helptext" style="margin-bottom:12px;font-size:13px;">
            ใช้สำหรับสินค้าที่มีหลายขนาด เช่น <strong>ท่อ PVC สีฟ้า</strong> แล้วไปผูกที่หน้าสินค้าพร้อมใส่ไซส์
            — ที่ POS จะรวมเป็นการ์ดเดียวให้เลือกขนาด
        </p>
        <table>
            <thead><tr><th>ชื่อกลุ่ม</th><th>จำนวนไซส์ / SKU</th><th></th></tr></thead>
            <tbody>
            @forelse($groups as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td><span class="badge info">{{ $item->products_count }} รายการ</span></td>
                    <td>
                        <button class="btn btn-outline btn-sm" type="button" onclick='openMaster(@json($item))'>แก้ไข</button>
                        <form method="POST" action="{{ route('product-groups.destroy', $item) }}" style="display:inline;" onsubmit="return confirmDelete(this, 'ยืนยันลบกลุ่มนี้?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">ลบ</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3"><div class="empty">ยังไม่มีกลุ่มสินค้า — กด “+ เพิ่มกลุ่ม” เพื่อเริ่มต้น</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const BASE = @json(url('/product-groups'));
function openMaster(item=null){
  const action = item ? `${BASE}/${item.id}` : BASE;
  openModal(`
    <div class="mh"><h3>${item?'แก้ไข':'เพิ่ม'}กลุ่มสินค้า</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>
    <form class="mb" method="POST" action="${action}">
      <input type="hidden" name="_token" value="${window.CSRF}">
      ${item?'<input type="hidden" name="_method" value="PUT">':''}
      <div class="field"><label>ชื่อกลุ่ม</label><input name="name" required value="${item?.name||''}" placeholder="เช่น ท่อ PVC สีฟ้า"></div>
      <div class="helptext">ตั้งชื่อให้ชัดเจน แล้วไปผูกที่หน้าสินค้า พร้อมใส่ไซส์แต่ละรายการ</div>
      <div class="mf" style="margin:16px -20px -18px;">
        <button class="btn btn-outline" type="button" onclick="closeModal()">ยกเลิก</button>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </div>
    </form>`);
}
</script>
@endpush
