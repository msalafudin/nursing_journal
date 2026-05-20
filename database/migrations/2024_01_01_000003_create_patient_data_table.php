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
        Schema::create('patient_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('unit_id');
            $table->date('date');
            $table->enum('shift', ['Pagi', 'Siang', 'Malam']);
            $table->json('data');
            $table->unsignedInteger('total_patients')->nullable();
            $table->timestamps();
            
            // Unique constraint for date/shift/unit combination
            $table->unique(['unit_id', 'date', 'shift'], 'unique_entry');
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            
            // Indexes
            $table->index('date');
            $table->index('shift');
            $table->index(['unit_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_data');
    }
};
