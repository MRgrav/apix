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
        //
        Schema::table('students', function (Blueprint $table) {
            // Modify the 'class' column to be nullable
            $table->string('class')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('students', function (Blueprint $table) {
            // Reverse the nullable change, making it non-nullable again
            $table->string('class')->nullable(false)->change();
        });
    }
};
