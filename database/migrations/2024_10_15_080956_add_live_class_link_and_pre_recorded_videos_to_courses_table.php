<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLiveClassLinkAndPreRecordedVideosToCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('live_class_link')->nullable()->after('some_existing_field'); // Adjust 'some_existing_field' as per your table structure
            $table->json('pre_recorded_videos')->nullable()->after('live_class_link');
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
            $table->dropColumn('live_class_link');
            $table->dropColumn('pre_recorded_videos');
        });
    }
}
