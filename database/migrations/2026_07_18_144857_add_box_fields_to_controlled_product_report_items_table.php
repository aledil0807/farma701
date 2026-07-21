<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_product_report_items', function (Blueprint $table) {
            $table->unsignedInteger('units_per_box')->default(1);
            $table->unsignedInteger('boxes_received')->default(0);
        });

        DB::statement('UPDATE controlled_product_report_items SET units_per_box = 1 WHERE units_per_box IS NULL OR units_per_box = 0');

        DB::statement('UPDATE controlled_product_report_items SET boxes_received = entries WHERE boxes_received = 0 AND entries > 0');
    }

    public function down(): void
    {
        Schema::table('controlled_product_report_items', function (Blueprint $table) {
            $table->dropColumn(['units_per_box', 'boxes_received']);
        });
    }
};