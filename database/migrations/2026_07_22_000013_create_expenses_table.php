<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('spent_at');
            $table->string('category', 100);
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 20)->default('cash'); // cash|transfer
            $table->string('paid_by_name')->nullable(); // คนเบิก/ผู้รับเงิน
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('spent_at');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
