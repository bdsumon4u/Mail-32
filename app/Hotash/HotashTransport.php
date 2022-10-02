<?php

namespace App\Hotash;

use App\Innoclapps\Contracts\MailClient\SmtpInterface;
use App\Innoclapps\Contracts\MailClient\SupportSaveToSentFolderParameter;
use App\Innoclapps\MailClient\Exceptions\ConnectionErrorException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;

class HotashTransport extends AbstractTransport
{
    use SystemEmail;

    public function __toString(): string
    {
        return 'hotash';
    }

    protected function doSend(SentMessage $message): void
    {
        /** @var \Symfony\Component\Mime\Email $original */
        // $original = $message->getOriginalMessage();
        // dd($original, $original->getAttachments());
        // , $this->rawAttachments, $this->diskAttachments);

        try {
            $client = $this->getSystemEmail()->getClient();

            // The mailables that are sent via email account are not supposed
            // to be saved in the sent folder to takes up space, however
            // email provider like Gmail does not allow to not save the mail
            // in the sent folder, in this case, we will check if the client
            // support to avoid saving the email in the sent folder
            // otherwise we will set custom header so these emails can be excluded from syncing
            if ($client->getSmtp() instanceof SupportSaveToSentFolderParameter) {
                $client->getSmtp()->saveToSentFolder(false);
            } else {
                $client->addHeader('X-Hotash-Mailable', 'true'); // HOTASH # bool(true)
            }

            try {
                tap($client, function (SmtpInterface $mailer) use ($message) {
                    $mailer->setMessage($message);
                    // $mailer->htmlBody($original->getHtmlBody())
                    //     ->textBody($original->getTextBody())
                    //     ->subject($original->getSubject())
                    //     ->to($this->addresses($original->getTo()))
                    //     ->cc($this->addresses($original->getCc()))
                    //     ->bcc($this->addresses($original->getBcc()))
                    //     ->replyTo($this->addresses($original->getReplyTo()))
                    //     ->attach(public_path('storage/u636182416_bsb.sql'))
                    //     ->attach(public_path('storage/DharmikPlanet.png'))
                    //     ->attachData('Sumon Ahmed', 'name.txt');
                    // $this->buildAttachmentsViaEmailClient($instance);
                })->send();
            } catch (ConnectionErrorException $e) {
                // Set Requires Authentication
                $this->getSystemEmail()->oAuthAccount->setRequiresAuthentication();
            } catch (TransportExceptionInterface $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->getLogger()->debug(sprintf('Email transport "%s" stopped', __CLASS__));
                throw $e;
            }
        } catch (ConnectionErrorException $e) {
            throw $e;
        }
    }

    private function addresses($addresses)
    {
        return collect($addresses)->map(fn (Address $address) => ['address' => $address->getEncodedAddress(), 'name' => $address->getName()])->toArray();
    }
}
