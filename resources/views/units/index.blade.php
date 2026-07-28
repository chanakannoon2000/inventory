@extends('layouts.app')

@section('title', 'หน่วยนับ')

@section('content')
<div class="panel">
    <div class="ph">
        <h3>หน่วยนับสินค้า <span class="badge neutral">{{ $units->count() }} รายการ</span></h3>
        <button class="btn btn-primary" type="button" onclick="openMaster()">+ เพิ่ม</button>
    </div>
    <div class="pb">
        <table>
            <thead><tr><th>ชื่อหน่วยนับ</th><th>ใช้งานในสินค้า</th><th></th></tr></thead>
            <tbody>
            @forelse($units as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td><span class="badge info">{{ $item->products_count }} SKU</span></td>
                    <td>
                        <button class="btn btn-outline btn-sm" type="button" onclick='openMaster(@json($item))'>แก้ไข</button>
                        <form method="POST" action="{{ route('units.destroy', $item) }}" style="display:inline;" onsubmit="return confirmDelete(this, 'ยืนยันลบหน่วยนับนี้?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">ลบ</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3"><div class="empty">ยังไม่มีข้อมูล</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const BASE = @json(url('/units'));
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
    <div class="mh"><h3>${item?'แก้ไข':'เพิ่ม'}หน่วยนับ</h3><button class="xbtn" type="button" onclick="closeModal()">✕</button></div>
    <form class="mb" method="POST" action="${action}">
      <input type="hidden" name="_token" value="${window.CSRF}">
      ${item?'<input type="hidden" name="_method" value="PUT">':''}
      <div class="field"><label>ชื่อหน่วยนับ</label><input name="name" required value="${escAttr(item?.name)}"></div>
      <div class="mf" style="margin:16px -20px -18px;">
        <button class="btn btn-outline" type="button" onclick="closeModal()">ยกเลิก</button>
        <button class="btn btn-primary" type="submit">บันทึก</button>
      </div>
    </form>`);
}
</script>
@endpush
