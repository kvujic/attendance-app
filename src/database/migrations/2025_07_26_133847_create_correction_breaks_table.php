<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_correction_id');
            $table->dateTime('requested_break_start')->nullable();
            $table->dateTime('requested_break_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_breaks');
    }
};
