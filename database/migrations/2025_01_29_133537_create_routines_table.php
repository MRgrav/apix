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
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->time('sun')->nullable();
            $table->time('mon')->nullable();
            $table->time('tue')->nullable();
            $table->time('wed')->nullable();
            $table->time('thu')->nullable();
            $table->time('fri')->nullable();
            $table->time('sat')->nullable();
            $table->year('session')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};
