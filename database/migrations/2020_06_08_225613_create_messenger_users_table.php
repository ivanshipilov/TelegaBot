<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessengerUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('messenger_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string("user_id_telegram");
            $table->string("user_name");
            $table->string("user_surname");
            $table->string("user_phone");
            $table->string("user_latitude");
            $table->string("user_longitude");
            $table->string("user_country");
            $table->string("user_city");
            $table->string("user_district");
            $table->string("user_street");
            $table->string("user_house");
            $table->string("user_index");
            $table->string("id_chat");
            $table->string('response');
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
        Schema::dropIfExists('messenger_users');
    }
}
