<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@shop.local'],
            ['name' => 'เจ้าของร้าน', 'password' => Hash::make('owner123'), 'role' => 'owner']
        );

        User::updateOrCreate(
            ['email' => 'cashier@shop.local'],
            ['name' => 'พนักงานขาย', 'password' => Hash::make('cashier123'), 'role' => 'cashier']
        );

        Setting::current()->update([
            'shop_name' => 'ร้านช่างระบบวัสดุก่อสร้าง',
            'cipher_key' => 'MONEYCRAFT',
            'promptpay_id' => '0800562377',
            'tax_rate' => 7,
            'receipt_running' => 1000,
        ]);

        $categories = [
            ['name' => 'ปูนซีเมนต์ & คอนกรีต', 'barcode_prefix' => 'C', 'icon' => '🧱', 'color' => '#D9D2C2'],
            ['name' => 'เหล็กเส้น / เหล็กรูปพรรณ', 'barcode_prefix' => 'S', 'icon' => '⛓️', 'color' => '#CBD3D9'],
            ['name' => 'ท่อ-ข้อต่อ PVC/PPR', 'barcode_prefix' => 'P', 'icon' => '🧵', 'color' => '#CFE0EA'],
            ['name' => 'สี & เคมีภัณฑ์ก่อสร้าง', 'barcode_prefix' => 'D', 'icon' => '🎨', 'color' => '#F0D9C6'],
            ['name' => 'อิฐ-บล็อก', 'barcode_prefix' => 'B', 'icon' => '🧱', 'color' => '#E3C6B8'],
            ['name' => 'กระเบื้อง', 'barcode_prefix' => 'A', 'icon' => '◻️', 'color' => '#DCE6DE'],
            ['name' => 'สายไฟ-อุปกรณ์ไฟฟ้า', 'barcode_prefix' => 'E', 'icon' => '🔌', 'color' => '#F2E0B0'],
            ['name' => 'ฮาร์ดแวร์ (น็อต/สกรู)', 'barcode_prefix' => 'H', 'icon' => '🔩', 'color' => '#D6D6D6'],
        ];

        $catIds = [];
        foreach ($categories as $c) {
            $catIds[$c['name']] = Category::updateOrCreate(['name' => $c['name']], $c)->id;
        }

        $unitNames = ['ถุง', 'ชิ้น', 'เส้น', 'กก.', 'แผ่น', 'ม้วน', 'กล่อง', 'ลิตร', 'ลบ.ม.'];
        $unitIds = [];
        foreach ($unitNames as $name) {
            $unitIds[$name] = Unit::updateOrCreate(['name' => $name])->id;
        }

        $suppliers = [
            ['name' => 'SCG ผลิตภัณฑ์ก่อสร้าง', 'contact' => '02-586-3333', 'website' => 'scg.com'],
            ['name' => 'สยามเหล็กรุ่งเรือง', 'contact' => '081-234-5678', 'website' => 'siamsteel-example.com'],
            ['name' => 'ไทยพลาสติกท่อ', 'contact' => '02-111-2222', 'website' => 'thaiplast-example.com'],
            ['name' => 'เบเยอร์สี', 'contact' => '02-999-1234', 'website' => 'beger.co.th'],
            ['name' => 'ไทยยูเนี่ยนไฟฟ้า', 'contact' => '089-888-7777', 'website' => 'thaiunion-elec-example.com'],
        ];

        $supIds = [];
        foreach ($suppliers as $s) {
            $supIds[$s['name']] = Supplier::updateOrCreate(['name' => $s['name']], $s)->id;
        }

        $products = [
            ['name' => 'ปูนซีเมนต์ปอร์ตแลนด์ ตราเสือ 50กก.', 'barcode' => '8850001100019', 'cat' => 'ปูนซีเมนต์ & คอนกรีต', 'unit' => 'ถุง', 'sup' => 'SCG ผลิตภัณฑ์ก่อสร้าง', 'cost' => 145, 'price' => 172, 'stock' => 340, 'min' => 100, 'max' => 600],
            ['name' => 'ปูนก่อสำเร็จรูป 50กก.', 'barcode' => '8850001100026', 'cat' => 'ปูนซีเมนต์ & คอนกรีต', 'unit' => 'ถุง', 'sup' => 'SCG ผลิตภัณฑ์ก่อสร้าง', 'cost' => 98, 'price' => 118, 'stock' => 60, 'min' => 80, 'max' => 400],
            ['name' => 'เหล็กเส้นกลม RB6 มม. x10ม.', 'barcode' => '8850002200013', 'cat' => 'เหล็กเส้น / เหล็กรูปพรรณ', 'unit' => 'เส้น', 'sup' => 'สยามเหล็กรุ่งเรือง', 'cost' => 62, 'price' => 79, 'stock' => 15, 'min' => 20, 'max' => 200],
            ['name' => 'เหล็กข้ออ้อย DB12 มม. x10ม.', 'barcode' => '8850002200020', 'cat' => 'เหล็กเส้น / เหล็กรูปพรรณ', 'unit' => 'เส้น', 'sup' => 'สยามเหล็กรุ่งเรือง', 'cost' => 210, 'price' => 255, 'stock' => 48, 'min' => 15, 'max' => 150],
            ['name' => 'ท่อ PVC สีฟ้า 4 นิ้ว x4ม.', 'barcode' => '8850003300017', 'cat' => 'ท่อ-ข้อต่อ PVC/PPR', 'unit' => 'เส้น', 'sup' => 'ไทยพลาสติกท่อ', 'cost' => 175, 'price' => 219, 'stock' => 9, 'min' => 10, 'max' => 120],
            ['name' => 'ข้อต่อตรง PVC 1/2 นิ้ว', 'barcode' => '8850003300024', 'cat' => 'ท่อ-ข้อต่อ PVC/PPR', 'unit' => 'ชิ้น', 'sup' => 'ไทยพลาสติกท่อ', 'cost' => 3.5, 'price' => 6, 'stock' => 520, 'min' => 100, 'max' => 1000],
            ['name' => 'สีทาภายนอก เบเยอร์คูล 5 แกลลอน', 'barcode' => '8850004400011', 'cat' => 'สี & เคมีภัณฑ์ก่อสร้าง', 'unit' => 'ลิตร', 'sup' => 'เบเยอร์สี', 'cost' => 980, 'price' => 1250, 'stock' => 22, 'min' => 10, 'max' => 80],
            ['name' => 'อิฐมอญ (ก้อน)', 'barcode' => '8850005500018', 'cat' => 'อิฐ-บล็อก', 'unit' => 'ชิ้น', 'sup' => null, 'cost' => 2.2, 'price' => 3.5, 'stock' => 8000, 'min' => 2000, 'max' => 20000],
            ['name' => 'บล็อกคอนกรีตกลวง 7 นิ้ว', 'barcode' => '8850005500025', 'cat' => 'อิฐ-บล็อก', 'unit' => 'ชิ้น', 'sup' => null, 'cost' => 11, 'price' => 16, 'stock' => 1450, 'min' => 500, 'max' => 5000],
            ['name' => 'กระเบื้องปูพื้น 60x60ซม. เกรดA', 'barcode' => '8850006600015', 'cat' => 'กระเบื้อง', 'unit' => 'แผ่น', 'sup' => null, 'cost' => 145, 'price' => 199, 'stock' => 310, 'min' => 100, 'max' => 1000],
            ['name' => 'สายไฟ THW 2.5 มม. (ม้วน 100ม.)', 'barcode' => '8850007700012', 'cat' => 'สายไฟ-อุปกรณ์ไฟฟ้า', 'unit' => 'ม้วน', 'sup' => 'ไทยยูเนี่ยนไฟฟ้า', 'cost' => 890, 'price' => 1090, 'stock' => 6, 'min' => 8, 'max' => 60],
            ['name' => 'น็อตตัวหนอน 6มม. (กล่อง 100ตัว)', 'barcode' => '8850008800019', 'cat' => 'ฮาร์ดแวร์ (น็อต/สกรู)', 'unit' => 'กล่อง', 'sup' => null, 'cost' => 45, 'price' => 65, 'stock' => 140, 'min' => 30, 'max' => 300],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['barcode' => $p['barcode']],
                [
                    'name' => $p['name'],
                    'category_id' => $catIds[$p['cat']],
                    'unit_id' => $unitIds[$p['unit']],
                    'supplier_id' => $p['sup'] ? $supIds[$p['sup']] : null,
                    'cost_price' => $p['cost'],
                    'sell_price' => $p['price'],
                    'stock' => $p['stock'],
                    'min_stock' => $p['min'],
                    'max_stock' => $p['max'],
                ]
            );
        }
    }
}
