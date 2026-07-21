<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('promptpay_id');
            }
            if (! Schema::hasColumn('settings', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('settings', 'bank_account_no')) {
                $table->string('bank_account_no', 32)->nullable()->after('bank_account_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach (['bank_name', 'bank_account_name', 'bank_account_no'] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
