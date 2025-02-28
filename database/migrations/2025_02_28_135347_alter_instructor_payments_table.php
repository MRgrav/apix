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
        Schema::table('instructor_payments', function (Blueprint $table) {
            $table->dropColumn(['per_class_payment','transaction','group_student_name']);
            $table->integer('year')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instructor_payments', function (Blueprint $table) {
            //
            $table->string('group_student_name',200)->nullable();
            $table->decimal('per_class_payment', 8, 2)->nullable();
            $table->string('transaction')->nullable();
            $table->dropColumn('year');
        });
    }
};
