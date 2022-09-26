<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.0.7
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2022 KONKORD DIGITAL
 */

namespace App\Innoclapps\MailClient\Compose;

use App\Innoclapps\Contracts\Repositories\MediaRepository;
use App\Innoclapps\MailClient\Client;
use App\Innoclapps\MailClient\FolderIdentifier;
use App\Innoclapps\Resources\MailPlaceholders;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use KubAT\PhpSimple\HtmlDomParser;

abstract class AbstractComposer
{
    use ForwardsCalls;

    /**
     * Create new AbstractComposer instance.
     *
     * @param  \App\Innoclapps\MailClient\Client  $client
     * @param  \App\Innoclapps\MailClient\FolderIdentifier|null  $sentFolder
     */
    public function __construct(protected Client $client, ?FolderIdentifier $sentFolder = null)
    {
        if ($sentFolder) {
            $this->setSentFolder($sentFolder);
        }
    }

    /**
     * Send the message
     *
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     */
    abstract public function send();

    /**
     * Set the account sent folder
     *
     * @param  \App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @return static
     */
    public function setSentFolder(FolderIdentifier $folder)
    {
        $this->client->setSentFolder(
            $this->client->getFolders()->find($folder)
        );

        return $this;
    }

    /**
     * Convert the media images from the given message to base64
     *
     * @param  string  $message
     * @return string
     */
    protected function convertMediaImagesToBase64($message)
    {
        if (! $message) {
            return $message;
        }

        return $message;

        // HOTASH #
        // $repository = resolve(MediaRepository::class);
        // $dom = HtmlDomParser::str_get_html($message);

        // foreach ($dom->find('img') as $image) {
        //     if (Str::startsWith($image->src, [
        //         rtrim(url(config('app.url'), '/')).'/media',
        //         'media',
        //         '/media',
        //     ]) && Str::endsWith($image->src, 'preview')) {
        //         if (preg_match('/[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}/', $image->src, $matches)) {
        //             // Find the inline attachment by token via the media repository
        //             $media = $repository->findByToken($matches[0]);
        //             $image->src = 'data:'.$media->mime_type.';base64,'.base64_encode($media->contents());
        //         }
        //     }
        // }

        // return $dom->save();
        // HOTASH #
    }

    /**
     * Pass dynamic methods onto the client instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return static
     */
    public function __call($method, $parameters)
    {
        // HOTASH #
        // if ($method === 'htmlBody') {
        //     // First we will clean up spaces from the editor and then
        //     // we will clean up the placeholders input fields when empty
        //     $parameters[0] = trim(str_replace(
        //         ['<p><br /></p>', '<p><br/></p>', '<p><br></p>', '<p>&nbsp;</p>'],
        //         "\n",
        //         MailPlaceholders::cleanUpWhenViaInputFields($parameters[0])
        //     ));

        //     // Next, we will convert the media images that are inline from the current server
        //     // to base64 images so the EmbeddedImagesProcessor can embed them inline
        //     // If we don't embed the images and use the URL directly and the user decide to
        //     // change his Concord CRM installation domain, the images won't longer works, for this reason
        //     // we need to embed them inline like any other email client
        //     $parameters[0] = $this->convertMediaImagesToBase64($parameters[0]);
        // }
        // HOTASH #

        $this->forwardCallTo(
            $this->client,
            $method,
            $parameters
        );

        return $this;
    }
}
