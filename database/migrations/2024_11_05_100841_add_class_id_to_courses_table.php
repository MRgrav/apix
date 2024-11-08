<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            // Add the class_id column as an unsigned big integer
            $table->unsignedBigInteger('classes_id')->nullable()->after('id');

            // Set up the foreign key constraint
            $table->foreign('classes_id')->references('id')->on('classes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            // Drop the foreign key constraint and the column
            $table->dropForeign(['classes_id']);
            $table->dropColumn('classes_id');
        });
    }
};
