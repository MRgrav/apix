<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('plan_name');
            $table->decimal('old_rate', 10, 2)->nullable();
            $table->decimal('current_rate', 10, 2);
            $table->string('category');
            $table->boolean('is_NRI')->default(false);
            $table->decimal('GST', 5, 2)->nullable();
            $table->decimal('final_rate', 10, 2);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_plans');
    }
}
