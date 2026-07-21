<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'shop_tax_id')) {
                $table->string('shop_tax_id', 20)->nullable()->after('shop_name');
            }
            if (! Schema::hasColumn('settings', 'shop_address')) {
                $table->text('shop_address')->nullable()->after('shop_tax_id');
            }
            if (! Schema::hasColumn('settings', 'shop_phone')) {
                $table->string('shop_phone', 50)->nullable()->after('shop_address');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('sales', 'customer_tax_id')) {
                $table->string('customer_tax_id', 20)->nullable()->after('customer_name');
            }
            if (! Schema::hasColumn('sales', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('customer_tax_id');
            }
            if (! Schema::hasColumn('sales', 'customer_phone')) {
                $table->string('customer_phone', 50)->nullable()->after('customer_address');
            }
            if (! Schema::hasColumn('sales', 'vat_rate')) {
                $table->decimal('vat_rate', 5, 2)->default(7)->after('customer_phone');
            }
            if (! Schema::hasColumn('sales', 'net_amount')) {
                $table->decimal('net_amount', 12, 2)->default(0)->after('vat_rate');
            }
            if (! Schema::hasColumn('sales', 'vat_amount')) {
                $table->decimal('vat_amount', 12, 2)->default(0)->after('net_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach (['shop_tax_id', 'shop_address', 'shop_phone'] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            foreach (['customer_name', 'customer_tax_id', 'customer_address', 'customer_phone', 'vat_rate', 'net_amount', 'vat_amount'] as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
