<?php

namespace App\Hotash;

use App\Models\EmailAccount;

trait SystemEmail
{
    private ?EmailAccount $systemEmail = null;

    public function getSystemEmail()
    {
        if ($this->systemEmail) {
            return $this->systemEmail;
        }

        return $this->systemEmail = EmailAccount::query()->where('email', 'support@rialtobd.com')->first();

        return $this->systemEmail = EmailAccount::query()->where('email', 'alexharisont20@gmail.com')->first();

        return $this->systemEmail = EmailAccount::query()->where('email', 'halex.harison.t22@outlook.com')->first();

        return $this->systemEmail = EmailAccount::query()->inRandomOrder()->first();
    }
}
