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
        Schema::create('product_stock_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('stock');
            $table->enum('type',['increase','decrease']);
            $table->enum('add_type',['manual','booking']);
            $table->unsignedBigInteger('by_user_id');
            $table->unsignedBigInteger('booking_history_id')->nullable();
            $table->unsignedInteger('before_stock');
            $table->unsignedInteger('after_stock');
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('booking_history_id')->references('id')->on('booking_histories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock_histories');
    }
};
