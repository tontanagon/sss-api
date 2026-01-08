<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('item_booking_histories', function (Blueprint $table) {
            $table->string('product_unit')->nullable()->after('product_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_booking_histories', function (Blueprint $table) {
            $table->dropColumn('product_unit');
        });
    }
};
