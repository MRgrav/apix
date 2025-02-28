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
            // Rename 'group_id' to 'group_student_name' and change its type to string
            $table->dropColumn('group_id');
            // $table->renameColumn('group_id', 'group_student_name');
            $table->string('group_student_name',200)->nullable();

            // Add a new integer column for months
            $table->integer('month')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instructor_payments', function (Blueprint $table) {
            // Revert the changes made in the up method
            // $table->renameColumn('group_student_name', 'group_id');
            // $table->unsignedBigInteger('group_id')->nullable()->change();
            $table->unsignedBigInteger('group_id')->nullable();
            // Drop the 'months' column
            $table->dropColumn(['month','group_student_name']);
        });
    }
};
