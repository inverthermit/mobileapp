<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::disableForeignKeyConstraints();
        Schema::create('i_user', function (Blueprint $table) {
            $table->increments('user_uid')->comment('this id should not be returned to client');
            $table->string('user_id')->comment('this id can be imported from google or FB');
            $table->string('password');
            $table->string('personal_alias');
            $table->string('email');
            $table->string('phone');
            $table->string('photo');
            $table->string('source')->comment('from google|facebook|itime|email|phone');
            $table->string('device_token');
            $table->string('device_id');
            $table->decimal('average_rating_value')->comment('caclulated when inserting rating');
            $table->string('timezone');
            $table->integer('last_signin_time');
            $table->integer('signin_count')->comment('for calculating the frequency');
            $table->string('status')->default('pending')->comment('pending|activated|unactivated');
            $table->timestamps();

            $table->index(['user_id', 'password']);
            $table->index('email');
            $table->index('phone');
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

        Schema::disableForeignKeyConstraints();
        Schema::drop('i_user');
        Schema::enableForeignKeyConstraints();
    }
}
