<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveAndTrialLinkToTrialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trials', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('course_id');
            $table->string('trial_link')->nullable()->after('is_active');
            $table->text('description')->nullable()->after('trial_link');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trials', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropColumn('trial_link');
            $table->dropColumn('description');
        });
    }
}
