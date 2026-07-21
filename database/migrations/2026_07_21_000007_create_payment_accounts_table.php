<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable(); // ชื่อเรียก เช่น บัญชีหลัก / พร้อมเพย์ร้าน
            $table->string('type', 20); // promptpay | bank
            $table->string('promptpay_id', 32)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_no', 32)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ย้ายค่าจาก settings เดิม (ถ้ามี)
        if (Schema::hasTable('settings')) {
            $s = DB::table('settings')->orderBy('id')->first();
            if ($s) {
                $pp = preg_replace('/\D+/', '', (string) ($s->promptpay_id ?? '')) ?: '0800562377';
                DB::table('payment_accounts')->insert([
                    'label' => 'พร้อมเพย์หลัก',
                    'type' => 'promptpay',
                    'promptpay_id' => $pp,
                    'is_enabled' => true,
                    'is_default' => true,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (! empty($s->bank_account_no ?? null)) {
                    DB::table('payment_accounts')->insert([
                        'label' => 'บัญชีธนาคาร',
                        'type' => 'bank',
                        'bank_name' => $s->bank_name ?? null,
                        'bank_account_name' => $s->bank_account_name ?? null,
                        'bank_account_no' => $s->bank_account_no ?? null,
                        'is_enabled' => true,
                        'is_default' => false,
                        'sort_order' => 2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_accounts');
    }
};
