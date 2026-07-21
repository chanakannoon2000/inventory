<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'line_enabled')) {
                $table->boolean('line_enabled')->default(false)->after('promptpay_id');
            }
            if (! Schema::hasColumn('settings', 'line_channel_token')) {
                $table->text('line_channel_token')->nullable()->after('line_enabled');
            }
            if (! Schema::hasColumn('settings', 'line_target_id')) {
                $table->string('line_target_id', 64)->nullable()->after('line_channel_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach (['line_enabled', 'line_channel_token', 'line_target_id'] as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
