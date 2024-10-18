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
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->string('file_path'); // Path to the uploaded file
            $table->string('file_name'); // Name of the uploaded file
            $table->string('mime_type')->nullable(); // MIME type of the file
            $table->unsignedBigInteger('size')->nullable(); // Size of the file
            $table->string('file_type'); // Type of the file (e.g., 'thumbnail')
            $table->unsignedBigInteger('uploaded_by')->nullable(); // User ID who uploaded the file
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('uploads');
    }
};
