<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('short_description', 255)->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('course_category_id')->nullable()->constrained('course_categories')->onDelete('cascade');

            // course info
            $table->longText('requirements')->nullable();
            $table->longText('outcomes')->nullable();
            $table->longText('faq')->nullable();
            $table->longText('tags')->nullable();

            // meta tags
            $table->longText('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->longText('meta_keywords')->nullable();
            $table->longText('meta_author')->nullable();
            $table->foreignId('meta_image')->nullable()->constrained('uploads')->onDelete('set null');

            // course media
            $table->foreignId('thumbnail')->nullable()->constrained('uploads')->onDelete('set null');
            $table->foreignId('course_overview_type')->nullable()->constrained('statuses')->onDelete('cascade');
            $table->string('video_url', 255)->nullable();
            $table->string('language', 255)->default('en');

            // course type
            $table->foreignId('course_type')->default(13)->constrained('statuses')->onDelete('cascade');
            $table->tinyInteger('is_admin')->default(11);

            // pricing
            $table->double('price', 16, 2)->nullable();
            $table->tinyInteger('is_discount')->default(10);
            $table->tinyInteger('discount_type')->default(1);
            $table->double('discount_price', 16, 2)->nullable();
            $table->date('discount_start_date')->nullable();
            $table->date('discount_end_date')->nullable();

            // instructors
            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->tinyInteger('is_multiple_instructor')->default(0);
            $table->json('partner_instructors')->nullable();

            $table->tinyInteger('is_free')->default(0);
            $table->foreignId('level_id')->default(18)->constrained('statuses')->onDelete('cascade');
            $table->foreignId('status_id')->default(3)->constrained('statuses')->onDelete('cascade');
            $table->foreignId('visibility_id')->default(22)->constrained('statuses')->onDelete('cascade');

            $table->timestamp('last_modified')->nullable();
            $table->double('rating')->default(0.00);
            $table->integer('total_review')->default(0);
            $table->integer('total_sales')->default(0);
            $table->double('course_duration')->default(0.00);
            $table->double('point', 8, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();

            // index
            $table->index(['title', 'is_free', 'status_id', 'instructor_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('courses');
    }
};
