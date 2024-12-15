<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            // $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('name', 255); // Group name
            $table->string('description', 255)->nullable(); // Group description
            $table->unsignedBigInteger('created_by')->nullable();
            // $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Admin/instructor who created the group
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('groups');
    }
};
