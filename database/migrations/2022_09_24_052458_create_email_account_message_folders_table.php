<?php

use App\Models\EmailAccountMessage;
use App\Models\EmailAccountMessageFolder;
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
        Schema::create('email_account_message_folders', function (Blueprint $table) {
            $table->foreignIdFor(EmailAccountMessage::class, 'message_id');
            $table->foreignIdFor(EmailAccountMessageFolder::class, 'folder_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_account_message_folders');
    }
};
