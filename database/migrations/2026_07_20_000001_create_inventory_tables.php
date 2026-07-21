<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('cashier')->after('password');
            }
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon', 16)->nullable();
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barcode')->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sell_price', 12, 2)->default(0);
            $table->string('image_url')->nullable();
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('max_stock', 12, 2)->default(100);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name')->default('ร้านวัสดุก่อสร้าง');
            $table->string('cipher_key', 10)->default('MONEYCRAFT');
            $table->decimal('tax_rate', 5, 2)->default(7);
            $table->unsignedInteger('receipt_running')->default(1000);
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('sold_at');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('qty', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['IN', 'OUT', 'ADJUST']);
            $table->decimal('qty', 12, 2);
            $table->foreignId('ref_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('units');
        Schema::dropIfExists('categories');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
