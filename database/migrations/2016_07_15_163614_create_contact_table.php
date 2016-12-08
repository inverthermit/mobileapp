<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::disableForeignKeyConstraints();
        Schema::create('i_contact', function (Blueprint $table) {

            // maybe need to add a field to show the friend level
            $table->integer('user_uid')->unsigned();
            $table->integer('contact_uid')->unsigned();
            $table->integer('relationship')->default(1)->comment('1: single, 2: duplex');
            $table->tinyInteger('rating_visibility')->comment('0|1');
            $table->tinyInteger('event_visibility')->comment('0: invisible, 1: freebusy, 2: visible');
            $table->string('source')->comment('e.g., google, facebook, itime');
            $table->string('alias_name');
            $table->string('alias_photo');
            $table->integer('catchup_count');
            $table->bigInteger('next_catchup_time');
            $table->bigInteger('last_catchup_time');
            $table->string('note');
            $table->string('status')->comment('unactivated|activated');
            $table->timestamps();

            $table->primary(['user_uid', 'contact_uid']);
            $table->foreign('user_uid')->references('user_uid')->on('i_user');
            $table->foreign('contact_uid')->references('user_uid')->on('i_user');

        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::disableForeignKeyConstraints();
        Schema::drop('i_contact');
        Schema::enableForeignKeyConstraints();


    }
}
