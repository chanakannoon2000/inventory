<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'promptpay_id')) {
                $table->string('promptpay_id', 32)->default('0800562377')->after('cipher_key');
            }
        });

        if (Schema::hasColumn('settings', 'promptpay_id')) {
            DB::table('settings')->where(function ($q) {
                $q->whereNull('promptpay_id')->orWhere('promptpay_id', '');
            })->update(['promptpay_id' => '0800562377']);
        }

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method', 20)->default('cash')->after('change_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'promptpay_id')) {
                $table->dropColumn('promptpay_id');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
