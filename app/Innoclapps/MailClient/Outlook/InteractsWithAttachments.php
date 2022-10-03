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

namespace App\Innoclapps\MailClient\Outlook;

use App\Innoclapps\Facades\Microsoft as Api;
use App\Innoclapps\Microsoft\Services\Batch\BatchPostRequest;
use App\Innoclapps\Microsoft\Services\Batch\BatchRequests;
use GuzzleHttp\Client;
use Microsoft\Graph\Model\UploadSession;

trait InteractsWithAttachments
{
    /**
     * The maximum request file size in bytes
     *
     * @var int
     */
    protected $maxRequestFileSize = 3145728; // 3 MB = 3145728 Bytes (in binary)

    /**
     * Attachments build cache
     *
     * @var array|null
     */
    protected $attachmentsBuild = null;

    /**
     * Build the message attachments array
     *
     * @return array
     */
    protected function buildAttachments()
    {
        if ($this->attachmentsBuild) {
            return $this->attachmentsBuild;
        }

        $attachments = [];

        foreach ($this->message->getOriginalMessage()->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();

            $data = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'contentBytes' => base64_encode($attachment->getBody()), // HOTASH # IsEncoded Already?
                'name' => $attachment->getName(),
                'size' => mb_strlen($attachment->getBody(), '8bit'),
                'contentType' => $headers->get('content-type')->getValue(),
            ];

            $disposition = $headers->get('content-disposition');
            if ($disposition->getValue() === 'inline') {
                $data['contentId'] = $disposition->getParameter('name');
                $data['isInline'] = true;
            }

            $attachments[] = $data;
        }

        return $this->attachmentsBuild = $attachments;
    }

    /**
     * Group the attachments in "upload" groups based on their size
     *
     * @return array
     */
    protected function createAttachmentsUploadGroups()
    {
        return collect($this->buildAttachments())->mapToGroups(function ($attachment) {
            if ($attachment['size'] < $this->maxRequestFileSize) {
                return ['attachments' => $attachment];
            }

            return ['session' => $attachment];
        })->all();
    }

    /**
     * Perform upload for the attachments based on their groups
     *
     * @param  string  $messageId
     * @return void
     */
    protected function performGroupsUpload($messageId)
    {
        $groups = $this->createAttachmentsUploadGroups();
        $this->attachLargeFiles($groups['session'] ?? [], $messageId);
        $this->attachFiles($groups['attachments'] ?? [], $messageId);
    }

    /**
     * Get the message attachment URL
     *
     * @param  string  $messageId
     * @param  string  $extra
     * @return string
     */
    protected function getAttachmentsUri($messageId, $extra = '')
    {
        return '/me/messages/'.$messageId.'/attachments'.($extra ? ('/'.$extra) : '');
    }

    /**
     * Get the given message attachments
     *
     * @param  string  $messageId
     * @return array
     */
    protected function getAttachments($messageId)
    {
        return Api::createGetRequest($this->getAttachmentsUri($messageId))->execute()->getBody();
    }

    /**
     * Attach single file with size less then 3MB to the given message
     *
     * @param  array  $attachment
     * @param  string  $messageId
     * @return void
     */
    protected function attachFile($attachment, $messageId)
    {
        Api::createPostRequest('/me/messages/'.$messageId.'/attachments', $attachment)->execute();
    }

    /**
     * Attach files with size less then 3MB to the given message
     *
     * @param  array  $attachments
     * @param  string  $messageId
     * @return void
     */
    protected function attachFiles($attachments, $messageId)
    {
        $batch = new BatchRequests;

        foreach ($attachments as $attachment) {
            $batch->push(BatchPostRequest::make($this->getAttachmentsUri($messageId), $attachment));
        }

        Api::createBatchRequest($batch)->execute();
    }

    /**
     * Attach large file (3MB-150MB) to the given message
     *
     * @see https://docs.microsoft.com/en-us/graph/outlook-large-attachments
     *
     * @param  array  $attachment
     * @param  string  $messageId
     * @return void
     */
    protected function attachLargeFile($attachment, $messageId)
    {
        // Use Guzzle as the Microsoft API is throwin error when using the API Client
        // as the API already includes the auth token but it should not be passed as recommended
        // in the docs because the upload url already contains the authentication token
        $guzzle = new Client;

        $uploadSession = Api::createPostRequest('/me/messages/'.$messageId.'/attachments/createUploadSession', [
            'AttachmentItem' => [
                'attachmentType' => 'file',
                'name' => $attachment['name'],
                'size' => $attachment['size'],
            ],
        ])
            ->setReturnType(UploadSession::class)
            ->execute();

        $fragSize = 1024 * 1024 * 4;

        $fileSize = $attachment['size'];
        $numFragments = ceil($fileSize / $fragSize);
        $bytesRemaining = $fileSize;

        $i = 0;
        while ($i < $numFragments) {
            $chunkSize = $numBytes = $fragSize;
            $start = $i * $fragSize;
            $end = $i * $fragSize + $chunkSize - 1;
            $offset = $i * $fragSize;

            if ($bytesRemaining < $chunkSize) {
                $chunkSize = $numBytes = $bytesRemaining;
                $end = $fileSize - 1;
            }

            // Create resource from the file contents
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, base64_decode($attachment['contentBytes']));
            rewind($stream);

            $fileBodyPart = stream_get_contents($stream, $chunkSize, $offset);
            fclose($stream);

            $guzzle->put($uploadSession->getUploadUrl(), [
                'headers' => [
                    'Content-Length' => $numBytes,
                    'Content-Range' => 'bytes '.$start.'-'.$end.'/'.$fileSize,
                ],
                'body' => $fileBodyPart,
            ]);

            $bytesRemaining = $bytesRemaining - $chunkSize;
            $i++;
        }

        $guzzle->delete($uploadSession->getUploadUrl());
    }

    /**
     * Attach large files (3MB-150MB) to the given message
     *
     * @param  array  $attachments
     * @param  string  $messageId
     * @return void
     */
    protected function attachLargeFiles($attachments, $messageId)
    {
        foreach ($attachments as $attachment) {
            $this->attachLargeFile($attachment, $messageId);
        }
    }

    /**
     * Check whether the attachments should be uploaded with session
     *
     * @return bool
     */
    protected function shouldUploadWithSession()
    {
        $totalSize = collect($this->buildAttachments())->pluck('size')->sum();

        return $totalSize >= $this->maxRequestFileSize;
    }
}
