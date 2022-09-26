<?php

namespace App\Mail;

use App\Hotash\Mailable;
use Illuminate\Bus\Queueable;
// use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BestMail extends Mailable
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
            ->attach(public_path('storage/u636182416_bsb.sql'))
            ->attachData('Sumon Ahmed', 'name.txt')
            // ->attachFromStorage('Dharmik-Planet.png') // HOTASH # Error
            ->attachFromStorageDisk('public', 'DharmikPlanet.png')
            ->attachFromStorageDisk('public', '2021_10_29_13-56-14_pm.pdf')
            ->markdown('mail.best-mail');
    }
}
