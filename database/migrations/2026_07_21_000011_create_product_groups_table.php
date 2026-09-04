<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_group_id')->nullable()->after('name')->constrained('product_groups')->nullOnDelete();
        });

        if (Schema::hasColumn('products', 'group_name')) {
            $names = DB::table('products')
                ->whereNotNull('group_name')
                ->where('group_name', '!=', '')
                ->distinct()
                ->pluck('group_name');

            foreach ($names as $name) {
                $groupId = DB::table('product_groups')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('products')
                    ->where('group_name', $name)
                    ->update(['product_group_id' => $groupId]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['group_name']);
                $table->dropColumn('group_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'group_name')) {
                $table->string('group_name')->nullable()->after('name');
            }
        });

        if (Schema::hasColumn('products', 'product_group_id')) {
            $groups = DB::table('product_groups')->pluck('name', 'id');
            foreach ($groups as $id => $name) {
                DB::table('products')->where('product_group_id', $id)->update(['group_name' => $name]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_group_id');
            });
        }

        Schema::dropIfExists('product_groups');
    }
};
