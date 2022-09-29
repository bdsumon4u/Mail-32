<?php

namespace App\Mail;

// use App\Hotash\Mailable;

use App\Hotash\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            // ->to('havnet@tempverify.com', 'Hav Net')
            ->to('alexharisont20@gmail.com', 'Alex Hari')
            ->view('mail.test-mail');
    }
}
