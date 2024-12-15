<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->unsignedBigInteger('course_id')->nullable();
            // $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->integer('order')->default(1);
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            // $table->foreignId('status_id')->default(3)->constrained('statuses')->onDelete('cascade');
            // $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            // $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();

            // index
            $table->index(['title', 'course_id', 'order', 'status_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sections');
    }
};
