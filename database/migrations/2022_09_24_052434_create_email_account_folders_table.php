<?php

use App\Models\EmailAccount;
use App\Models\EmailAccountFolder;
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
        Schema::create('email_account_folders', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(EmailAccount::class, 'email_account_id');
            $table->foreignIdFor(EmailAccountFolder::class, 'parent_id')->nullable();

            $table->string('remote_id')->nullable()
                ->comment('API ID, uidvalidity etc...');

            $table->boolean('support_move')->default(true);
            $table->boolean('syncable')->default(false);
            $table->boolean('selectable')->default(false);

            $table->string('type')->nullable();

            $table->string('name');
            $table->string('display_name');

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
        Schema::dropIfExists('email_account_folders');
    }
};
