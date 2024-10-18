<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title', 191);
            $table->string('slug', 191)->unique();
            $table->foreignId('icon')->nullable()->constrained('uploads')->onDelete('cascade');
            $table->foreignId('thumbnail')->nullable()->constrained('uploads')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('course_categories')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('status_id')->default(1)->constrained('statuses')->onDelete('cascade');
            $table->tinyInteger('is_popular')->default(0);
            $table->timestamps();

            // index
            $table->index(['title', 'status_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_categories');
    }
};
