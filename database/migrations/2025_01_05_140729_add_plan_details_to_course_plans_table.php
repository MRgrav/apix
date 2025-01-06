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
        Schema::table('course_plans', function (Blueprint $table) {
            //
            $table->string('plan_details')->after('plan_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_plans', function (Blueprint $table) {
            //
            $table->dropColumn('plan_details');
        });
    }
};