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
        Schema::create('booking_histories', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 12);
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->string('user_code');
            $table->string('user_grade');
            $table->string('phone_number', 20);
            $table->dateTime('return_at');
            $table->string('subject');
            $table->string('teacher');
            $table->string('activity_name');
            $table->unsignedInteger('participants');
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
            ])->default('pending');
            // $table->text('remark')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_histories');
    }
};
