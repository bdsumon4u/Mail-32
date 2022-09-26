<?php

use App\Models\User;
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
        Schema::create('oauth_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('type'); // HOTASH #
            $table->foreignIdFor(User::class);
            $table->string('oauth_user_id');

            $table->string('email')->nullable(); // HOTASH #
            $table->boolean('requires_auth')->default(false);
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('expires'); // HOTASH #

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
        Schema::dropIfExists('oauth_accounts');
    }
};
