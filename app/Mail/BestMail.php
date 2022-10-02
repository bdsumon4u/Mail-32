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
        // All can send attachments.
        // Inline attachment does not work with outlook.
        // It sends but previews, also with wrong name.
        // Gmail can send from .and+ mails.
        // Outlook can not send from any other mail.
        // Imap can send from outlook or gmail.
        // But not from random or .and+ mails.
        return $this
            ->from('halex.harison.t22@outlook.com') // 'no-reply@rialtobd.com' not working
            ->to('halexharisont22@gmail.com', 'Halex Harison')
            ->bcc(['Alex Hari' => 'havnet@tempverify.com', 'TempMail' => 'cojekix887@migonom.com'])
            ->bcc(['Brad Riordan' => 'bradlriordan@gmail.com'])
            ->replyTo('contact@rialtobd.com', 'RialToBD Office')
            ->replyTo('office@rialtobd.com', 'RialToBD Office')
            ->attach(public_path('storage/u636182416_bsb.sql'))
            ->attachData('Sumon Ahmed', 'name.txt')
            // ->attachFromStorage('public/Dharmik-Planet.png') // HOTASH # Error
            // ->attachFromStorage('public/IC4ME2.pdf') // HOTASH # Error
            ->attachFromStorageDisk('public', 'DharmikPlanet.png')
            ->attachFromStorageDisk('public', '2021_10_29_13-56-14_pm.pdf')
            ->view('mail.test-mail')
            // ->text('mail.text-mail')
;
    }
}
