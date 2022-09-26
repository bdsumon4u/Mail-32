<?php

use App\Models\EmailAccountMessage;
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
        Schema::create('email_account_message_addresses', function (Blueprint $table) {
            $table->foreignIdFor(EmailAccountMessage::class, 'message_id');

            $table->string('address')->nullable(); // For drafts without address
            $table->string('name')->nullable();

            $table->string('address_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_account_message_addresses');
    }
};
