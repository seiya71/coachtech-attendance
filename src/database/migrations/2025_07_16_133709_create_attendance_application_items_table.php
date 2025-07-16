<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceApplicationItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_application_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_application_id')->constrained();
            $table->foreignId('break_time_id')->nullable();
            $table->string('field');
            $table->time('value');
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
        Schema::dropIfExists('attendance_application_items');
    }
}
