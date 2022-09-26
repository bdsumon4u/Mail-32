<?php

namespace Database\Seeders;

use App\Enums\ConnectionType;
use App\Models\EmailAccount;
use Illuminate\Database\Seeder;

class EmailAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EmailAccount::query()->updateOrCreate([
            'email' => 'alexharisont20@gmail.com',
        ], [
            'connection_type' => ConnectionType::Gmail->value,
            'initial_sync_from' => now()->toDateTimeString(),
        ]);

        EmailAccount::query()->updateOrCreate([
            'email' => 'alexharisont20@outlook.com',
        ], [
            'connection_type' => ConnectionType::Outlook->value,
            'initial_sync_from' => now()->toDateTimeString(),
        ]);

        EmailAccount::query()->updateOrCreate([
            'email' => 'bradlriordan@gmail.com',
        ], [
            'connection_type' => ConnectionType::Gmail->value,
            'initial_sync_from' => now()->toDateTimeString(),
        ]);

        EmailAccount::query()->updateOrCreate([
            'email' => 'support@rialtobd.com',
        ], [
            'password' => '@Cyber32.com',
            'connection_type' => ConnectionType::Imap->value,
            'initial_sync_from' => now()->toDateTimeString(),
            'validate_cert' => false, // HOTASH #
            'username' => 'support@rialtobd.com',
            'imap_server' => 'mail.rialtobd.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_server' => 'mail.rialtobd.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ]);
    }
}
