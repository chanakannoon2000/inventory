# ระบบคลังสินค้า + POS วัสดุก่อสร้าง

PHP MVC (Laravel) + MySQL (`inventory`) สำหรับจัดการคลังและขายหน้าร้าน

## ความต้องการ
- PHP 8.4+ (แนะนำ Laragon PHP 8.4)
- MySQL / MariaDB
- extension: `pdo_mysql`, `gd`, `mbstring`, `openssl`

## ติดตั้งฐานข้อมูล

### วิธีที่ 1: phpMyAdmin
1. เปิด phpMyAdmin → สร้าง Database ชื่อ `inventory` (utf8mb4)
2. Import ไฟล์ `database/sql/inventory_schema.sql` (ถ้าต้องการสร้างตารางด้วยมือ)
3. หรือข้ามขั้นตอน import แล้วใช้ migrate ด้านล่างแทน

### วิธีที่ 2: Laravel Migrate + Seed
```bash
# ใช้ PHP 8.4 ของ Laragon
C:\laragon\bin\php\php-8.4.23-Win32-vs17-x64\php.exe artisan migrate:fresh --seed
C:\laragon\bin\php\php-8.4.23-Win32-vs17-x64\php.exe artisan storage:link
```

ตั้งค่าใน `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory
DB_USERNAME=root
DB_PASSWORD=
```

## รันระบบ
- ผ่าน Laragon: เปิด `http://inventory.test` (หรือ virtual host ของโฟลเดอร์นี้)
- หรือ: `php artisan serve` แล้วเปิด `http://127.0.0.1:8000`

## บัญชีทดสอบ
| บทบาท | อีเมล | รหัสผ่าน |
|--------|--------|----------|
| เจ้าของร้าน (เห็นราคาทุน) | owner@shop.local | owner123 |
| แคชเชียร์ (เห็นแค่ราคาขาย) | cashier@shop.local | cashier123 |

## ฟีเจอร์หลัก
- Product Master + Cost Cipher + Max-Min stock + รูปภาพ
- POS สแกนบาร์โค้ด / ตะกร้า / คิดเงิน / ใบเสร็จ + QR
- Master: Category / Unit / Supplier
- Dashboard + Report
- Backup JSON / CSV (owner)
