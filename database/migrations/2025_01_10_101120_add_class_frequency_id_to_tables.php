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
        Schema::table('payment_orders', function (Blueprint $table) {
            //
            $table->integer('number_of_classes')->nullable();
            $table->unsignedBigInteger('class_frequency_id')->nullable();
        });
        
        Schema::table('purchases', function (Blueprint $table) {
            //
            $table->integer('number_of_classes')->nullable();
            $table->unsignedBigInteger('class_frequency_id')->nullable();
        });

        Schema::table('group_user', function (Blueprint $table) {
            //
            $table->integer('class_counted')->nullable();
            $table->integer('total_classes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            //
            $table->dropColumn(['number_of_classes','class_frequency_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            //
            $table->dropColumn(['number_of_classes','class_frequency_id']);
        });

        Schema::table('group_user', function (Blueprint $table) {
            //
            $table->dropColumn(['class_counted','total_classes']);
        });
    }
};
