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
        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->enum('status', [
                'pending',
                'approved',
                'packed',
                'inuse',
                'returned',
                'overdue',
                'completed',
                'incomplete',
                'reject',
            ]);
            $table->text('remark')->nullable();
            $table->string('approve_by')->default('application');
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('booking_histories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_status_history');
    }
};
