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
        Schema::table('group_user', function (Blueprint $table) {
            $table->string('class',20)->nullable();
            $table->unsignedBigInteger('category')->nullable();
        });

        Schema::table('purchase', function (Blueprint $table) {
            $table->string('class',20)->nullable();
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->string('class',20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropColumn(['class','category']);
        });
        Schema::table('purchase', function (Blueprint $table) {
            $table->dropColumn('class');
        });
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn('class');
        });
    }
};
