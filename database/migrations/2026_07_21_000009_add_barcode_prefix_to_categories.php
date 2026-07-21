<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('barcode_prefix', 2)->nullable()->after('name');
        });

        $map = [
            'กระเบื้อง' => 'A',
            'อิฐ-บล็อก' => 'B',
            'ปูนซีเมนต์ & คอนกรีต' => 'C',
            'สี & เคมีภัณฑ์ก่อสร้าง' => 'D',
            'สายไฟ-อุปกรณ์ไฟฟ้า' => 'E',
            'ฮาร์ดแวร์ (น็อต/สกรู)' => 'H',
            'ท่อ-ข้อต่อ PVC/PPR' => 'P',
            'เหล็กเส้น / เหล็กรูปพรรณ' => 'S',
        ];

        foreach ($map as $name => $prefix) {
            DB::table('categories')->where('name', $name)->update(['barcode_prefix' => $prefix]);
        }

        // หมวดอื่นที่ยังไม่มี prefix — ใส่อัตโนมัติ A-Z ที่ว่าง
        $used = DB::table('categories')->whereNotNull('barcode_prefix')->pluck('barcode_prefix')->all();
        $letters = range('A', 'Z');
        $available = array_values(array_diff($letters, $used));

        $missing = DB::table('categories')->whereNull('barcode_prefix')->orderBy('id')->get();
        foreach ($missing as $i => $cat) {
            $prefix = $available[$i] ?? 'X';
            DB::table('categories')->where('id', $cat->id)->update(['barcode_prefix' => $prefix]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('barcode_prefix');
        });
    }
};
