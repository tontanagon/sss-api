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
        Schema::create('item_booking_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');   
            $table->string('product_name');
            $table->unsignedBigInteger('product_stock_history_id');
            $table->unsignedBigInteger('booking_history_id');
            $table->text('product_category');
            $table->string('product_type');
            $table->unsignedInteger('product_quantity');
            $table->unsignedInteger('product_quantity_return');
            $table->enum('status',['pending','received']);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_stock_history_id')->references('id')->on('product_stock_histories')->onDelete('cascade');
            $table->foreign('booking_history_id')->references('id')->on('booking_histories')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_booking_histories');
    }
};
