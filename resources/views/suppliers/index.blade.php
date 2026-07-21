@extends('layouts.app')

@section('title', 'ผู้จำหน่าย / Supplier')

@section('content')
<div class="panel">
    <div class="ph">
        <h3>ผู้จำหน่าย (Supplier) <span class="badge neutral">{{ $suppliers->count() }} รายการ</span></h3>
        <button class="btn btn-primary" type="button" onclick="openMaster()">+ เพิ่ม</button>
    </div>
    <div class="pb">
        <table>
            <thead><tr><th>ชื่อผู้จำหน่าย</th><th>เบอร์ติดต่อ</th><th>เว็บไซต์ / ช่องทางสั่งซื้อ</th><th>ใช้งานในสินค้า</th><th></th></tr></thead>
            <tbody>
            @forelse($suppliers as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->contact ?: '—' }}</td>
                    <td>{{ $item->website ?: '—' }}</td>
                    <td><span class="badge info">{{ $item->products_count }} SKU</span></td>
                    <td>
                        <button class="btn btn-outline btn-sm" type="button" onclick='openMaster(@json($item))'>แก้ไข</button>
                        <form method="POST" action="{{ route('suppliers.destroy', $item) }}" style="display:inline;" onsubmit="return confirmDelete(this, 'ยืนยันลบผู้จำหน่ายนี้?')">
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
const BASE = @json(url('/suppliers'));
function openMaster(item=null){
  const action = item ? `${BASE}/${item.id}` : BASE;
  openModal(`
    <div class="mh"><h3>${item?'แก้ไข':'เพิ่ม'}ผู้จำหน่าย</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>
    <form class="mb" method="POST" action="${action}">
      <input type="hidden" name="_token" value="${window.CSRF}">
      ${item?'<input type="hidden" name="_method" value="PUT">':''}
      <div class="field"><label>ชื่อผู้จำหน่าย</label><input name="name" required value="${item?.name||''}"></div>
      <div class="field"><label>เบอร์ติดต่อ</label><input name="contact" value="${item?.contact||''}"></div>
      <div class="field"><label>เว็บไซต์ / ช่องทางสั่งซื้อ</label><input name="website" value="${item?.website||''}"></div>
      <div class="mf" style="margin:16px -20px -18px;">
        <button class="btn btn-outline" type="button" onclick="closeModal()">ยกเลิก</button>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </div>
    </form>`);
}
</script>
@endpush
