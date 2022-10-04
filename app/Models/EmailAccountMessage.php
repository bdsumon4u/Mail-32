<?php

namespace App\Models;

use App\Events\EmailAccountMessageCreated;
use App\Innoclapps\MailClient\AbstractMessage;
use App\Support\EmailAccountMessageBody;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;

class EmailAccountMessage extends Model
{
    use HasFactory;

    /**
     * Message addresses headers and relations
     *
     * @var array
     */
    protected $addresses = ['from', 'to', 'cc', 'bcc', 'replyTo', 'sender'];

    /**
     * Searchable fields
     *
     * @var array
     */
    protected static $fieldSearchable = [
        'subject' => 'like',
        'text_body' => 'like',
        'html_body' => 'like',
        'from.address' => 'like',
        'from.name' => 'like',
    ];

    /**
     * Cache account folders
     *
     * When creating a lot messages we don't want
     *
     * thousands of queries to be executed
     *
     * @var array
     */
    protected $cachedAccountFolders = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email_account_id', 'remote_id', 'message_id',
        'subject', 'html_body', 'text_body', 'is_read',
        'is_draft', 'date', 'is_sent_via_app',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * Proper casts must be added to ensure the isDirty() method works fine
     * when checking whether the message is updated to broadcast to the front-end via sync
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date' => 'datetime',
        'is_draft' => 'boolean',
        'is_read' => 'boolean',
        'is_sent_via_app' => 'boolean',
        'email_account_id' => 'int',
    ];

    /**
     * A messages belongs to email account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<EmailAccount, EmailAccountMessage>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'email_account_id');
    }

    /**
     * A message belongs to many folders
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<EmailAccountFolder>
     */
    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(
            EmailAccountFolder::class,
            'email_account_message_folders',
            'message_id',
            'folder_id'
        )
            ->using(EmailAccountMessageFolder::class);
    }

    /**
     * A message has many addresses
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailAccountMessageAddress>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(EmailAccountMessageAddress::class, 'message_id');
    }

    /**
     * A message from address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<EmailAccountMessageAddress>
     */
    public function from(): HasOne
    {
        return $this->hasOne(EmailAccountMessageAddress::class, 'message_id')
            ->where('address_type', 'from');
    }

    /**
     * A message sender address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<EmailAccountMessageAddress>
     */
    public function sender(): HasOne
    {
        return $this->hasOne(EmailAccountMessageAddress::class, 'message_id')
            ->where('address_type', 'sender');
    }

    /**
     * A message to address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailAccountMessageAddress>
     */
    public function to(): HasMany
    {
        return $this->addresses()->where('address_type', 'to');
    }

    /**
     * A message cc address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailAccountMessageAddress>
     */
    public function cc(): HasMany
    {
        return $this->addresses()->where('address_type', 'cc');
    }

    /**
     * A message bcc address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailAccountMessageAddress>
     */
    public function bcc(): HasMany
    {
        return $this->addresses()->where('address_type', 'bcc');
    }

    /**
     * A message replyTo address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailAccountMessageAddress>
     */
    public function replyTo(): HasMany
    {
        return $this->addresses()->where('address_type', 'replyTo');
    }

    /**
     * A message has many headers
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailAccountMessageHeader>
     */
    public function headers(): HasMany
    {
        return $this->hasMany(EmailAccountMessageHeader::class, 'message_id');
    }

    /**
     * Get the model display name
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->subject);
    }

    /**
     * Get the URL path
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function path(): Attribute
    {
        return Attribute::get(function () {
            $accountId = $this->email_account_id;
            $folderId = $this->folders->first()->getKey();
            $messageId = $this->getKey();

            return "/inbox/$accountId/folder/$folderId/messages/$messageId";
        });
    }

    // HOTASH #
    // /**
    //  * Get the message attachments excluding the inline
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
    //  */
    // public function attachments()
    // {
    //     return static::media()->wherePivot('tag', '!=', 'embedded-attachments');
    // }

    // /**
    //  * Get the message inline attachments
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
    //  */
    // public function inlineAttachments()
    // {
    //     return static::media()->wherePivot('tag', '=', 'embedded-attachments');
    // }
    // HOTASH #

    /**
     * Determine if the message is a reply
     *
     * @return bool
     */
    public function isReply(): bool
    {
        return ! is_null($this->headers->firstWhere('name', 'in-reply-to'))
            || ! is_null($this->headers->firstWhere('name', 'references'));
    }

    /**
     * Get the previewText attribute
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function previewText(): Attribute
    {
        return Attribute::get(fn () => $this->body()->previewText());
    }

    /**
     * Get the visibleText attribute without any quoted content
     *
     * NOTE: Sometimes the EmailParser may fail because it won't be able
     * to recognize the quoted text and will return empty message
     * In this case, just return the original preview text
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function visibleText(): Attribute
    {
        return Attribute::get(fn () => $this->body()->visibleText());
    }

    /**
     * Get the hiddenText attribute
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hiddenText(): Attribute
    {
        return Attribute::get(fn () => $this->body()->hiddenText());
    }

    /**
     * Get the message body
     *
     * @return \App\Support\EmailAccountMessageBody
     */
    public function body()
    {
        return once(function () {
            return new EmailAccountMessageBody($this);
        });
    }

    // REPOSITORY #

    /**
     * Get database uids for a given folder
     *
     * @param  int  $folderId
     * @param  array  $columns
     * @return \Illuminate\Support\LazyCollection
     */
    public function getUidsByFolder($folderId, $columns = ['remote_id'])
    {
        return $this->select($columns)->whereHas('folders', function ($query) use ($folderId) {
            return $query->where('folder_id', $folderId);
        })->cursor();
    }

    /**
     * Get database uids for a given folder
     *
     * @param  int  $accountId
     * @param  array  $columns
     * @return \Illuminate\Support\LazyCollection
     */
    public function getUidsByAccount($accountId, $columns = ['remote_id'])
    {
        return $this->select($columns)->where('email_account_id', $accountId)->cursor();
    }

    /**
     * Get messages for account
     *
     * @param  int  $accountId
     * @param  \App\Innoclapps\MailClient\AbstractMessage  $message
     * @param  array|null  $associations
     * @return \App\Models\EmailAccountMessage
     */
    public function createForAccount($accountId, AbstractMessage $message, ?array $associations = null)
    {
        $data = $message->toArray();

        $dbMessage = static::create(array_merge($data, [
            'email_account_id' => $accountId,
            'is_sent_via_app' => $message->isSentFromApplication(),
        ]));

        $this->persistAddresses($data, $dbMessage);
        $this->persistHeaders($message, $dbMessage);
        $this->handleAttachments($dbMessage, $message); // HOTASH #

        $dbMessage->folders()->sync(
            $this->determineMessageDatabaseFolders($message, $dbMessage)
        );

        // HOTASH #
        // When associations are passed manually
        // this means that the user can manually associate the message
        // to resources, in this case, we use the user associations
        // after that for each reply from the client for this messages, the user
        // selected associations are used.
        // if ($associations) {
        //     $this->attachAssociations('emails', $dbMessage->getKey(), $associations);
        // } else {
        //     if ($dbMessage->isReply()) {
        //         $this->syncAssociationsWhenReply($dbMessage, $message);
        //     } else {
        //         // If the message is queued, we need to fetch the associations from
        //         // the headers and sync with the actual associations
        //         $this->syncAssociationsViaMessageHeaders($dbMessage->id, $message);
        //     }
        // }

        event(new EmailAccountMessageCreated($dbMessage, $message));

        return $dbMessage;
    }

    /**
     * Determine the message database folders
     * based on the message folder ID's
     *
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $imapMessage
     * @param  \App\Models\EmailAccountMessage  $dbMessage
     * @return array
     */
    protected function determineMessageDatabaseFolders($imapMessage, $dbMessage)
    {
        if (isset($this->cachedAccountFolders[$dbMessage->email_account_id])) {
            $folders = $this->cachedAccountFolders[$dbMessage->email_account_id];
        } else {
            $folders = $this->cachedAccountFolders[$dbMessage->email_account_id] = $dbMessage->account->folders;
            // For identifier looping in EmailAccountFolderCollection, avoids lazy loading protection
            $folders->loadMissing('account');
        }

        return $folders->findWhereIdentifierIn($imapMessage->getFolders())->pluck('id')->all();
    }

    /**
     * Save the message attachments
     *
     * @param  \App\Models\EmailAccountMessage  $message
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $imapMessage
     * @return array
     */
    protected function handleAttachments($dbMessage, $imapMessage)
    {
        // Store embedded attachments with embedded-attachments tag
        // We will cast as embedded/inline attachments only the attachments which
        // exists in the message body with src="cid_CONTENT_ID"
        $embeddedAttachments = $this->replaceBodyInlineAttachments($dbMessage, $imapMessage);

        // Remove the embedded attachments as they are stored with different tag
        $attachments = $imapMessage->getAttachments()
            ->reject(function ($attachment, $key) use ($embeddedAttachments) {
                return in_array($key, $embeddedAttachments);
            })->values();

        // Store non-embedded attachments
        return $this->storeAttachments($attachments, $dbMessage, 'attachments');
    }

    /**
     * Replace the message body inline attachments with the actual media links
     *
     * @param \App\Models\EmailAccountMessage
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $imapMessage
     * @return array
     */
    protected function replaceBodyInlineAttachments($dbMessage, $imapMessage)
    {
        $embeddedAttachmentsKeys = [];

        // We will provide a closure to the getPreviewBody method
        // to provide a custom content for the replace
        $replaceCallback = function ($file) use ($dbMessage, $imapMessage, &$embeddedAttachmentsKeys) {
            foreach ($imapMessage->getAttachments() as $key => $attachment) {
                if ($attachment->getContentId() === $file->getContentId()) {
                    // Check if the attachment with this content-id is already stored
                    // if yes, we will return the same media preview url
                    // Useful e.q. on update when the message already exists and
                    // we are trying to update it
                    $media = $dbMessage->inlineAttachments->first(function ($inlineMedia) use ($file) {
                        return $inlineMedia->getMeta('content-id') === $file->getContentId();
                    });

                    // When no media with this content-id found, we will create
                    // the media as embedded attachment and will set the meta content-id
                    if (is_null($media) &&
                    $media = $this->storeAttachments($attachment, $dbMessage, 'embedded-attachments')[0] ?? null
                    ) {
                        $media->setMeta('content-id', $file->getContentId());
                    }

                    if ($media) {
                        $embeddedAttachmentsKeys[] = $key;

                        return $media->getPreviewUri();
                    }
                }
            }
        };

        $this->update(['html_body' => $imapMessage->getPreviewBody($replaceCallback)], $dbMessage->id);

        return $embeddedAttachmentsKeys;
    }

    /**
     * Store message attachments
     *
     * @param  \Iluminate\Support\Collection|\App\Innoclapps\Contracts\MailClient\AttachmentInterface  $attachments
     * @param  \App\Models\EmailAccountMessage  $message
     * @param  string  $tag
     * @return array
     */
    protected function storeAttachments($attachments, $message, $tag)
    {
        if ($attachments instanceof AttachmentInterface) {
            $attachments = [$attachments];
        }

        $storedMedias = [];
        $allowedExtensions = config('mediable.allowed_extensions');

        foreach ($attachments as  $attachment) {
            $tmpFile = tmpfile();
            fwrite(
                $tmpFile,
                ContentDecoder::decode($attachment->getContent(), $attachment->getEncoding())
            );

            try {
                $storedMedias[] = $media = MediaUploader::fromSource($tmpFile)
                    ->toDirectory($message->getMediaDirectory())
                    ->useFilename($filename = pathinfo($attachment->getFileName(), PATHINFO_FILENAME))
                    // Allow any extension
                    ->setAllowedExtensions(array_unique(
                        array_merge($allowedExtensions, [pathinfo($attachment->getFileName(), PATHINFO_EXTENSION)])
                    ))
                    ->upload();
                $message->attachMedia($media, [$tag]);
            } catch (MediaUploadException $e) {
                Log::debug(
                    sprintf(
                        'Failed to store mail message [ID: %s] attachment, filename: %s, exception message: %s',
                        $message->getKey(),
                        $filename,
                        $e->getMessage()
                    ),
                );

                continue;
            } finally {
                // If the media package did not closed the file, close it
                // As per the tests, it looks like the package closes the tmpfile
                if (is_resource($tmpFile)) {
                    fclose($tmpFile);
                }
            }
        }

        return $storedMedias;
    }

    /**
     * Update a message for a given account
     *
     * NOTE: This functions does not syncs attachments
     *
     * @param  \App\Innoclapps\MailClient\AbstractMessage  $message
     * @param  int  $id The message ID
     * @return \App\Models\EmailAccountMessage
     */
    public function updateForAccount($message, $id)
    {
        $data = $message->toArray();
        $dbMessage = static::find($id);
        $dbMessage->update($data);

        $this->persistAddresses($data, $dbMessage);
        $this->persistHeaders($message, $dbMessage);
        // $this->replaceBodyInlineAttachments($dbMessage, $message); // HOTASH #

        $dbMessage->folders()->sync(
            $this->determineMessageDatabaseFolders($message, $dbMessage)
        );

        return $dbMessage;
    }

    /**
     * Delete account message(s)
     *
     * @param  int|\Illuminate\Database\Eloquent\Collection  $message
     * @param  null|int  $fromFolderId
     * @return void
     */
    public function deleteForAccount($message, $fromFolderId = null)
    {
        $eagerLoad = ['folders', 'account', 'account.trashFolder'];

        $messages = is_numeric($message) ?
            new Collection([$this->with($eagerLoad)->find($message)]) :
            $message->loadMissing($eagerLoad);

        $queue = $messages->mapToGroups(function ($message) {
            // When message is in the trash folder, we will parmanently delete
            // this message from the remote server
            if ($message->folders->find($message->account->trashFolder)) {
                return ['delete' => $message];
            }

            return ['move' => $message];
        });

        if (isset($queue['move'])) {
            $queue['move']->groupBy('email_account_id')
                ->each(function ($messages, $accountId) use ($fromFolderId) {
                    $this->batchMoveTo($messages, EmailAccount::with('trashFolder')->find($accountId)->trashFolder, $fromFolderId);
                });
        }

        if (isset($queue['delete'])) {
            $this->batchDelete($queue['delete']);
        }
    }

    /** IMAP */

    /**
     * Find the last synced uid by folder id
     * This is applied only for IMAP account as their last uid
     * may be guaranteed to be integer
     *
     * @param  int  $folderId
     * @return null|int
     */
    public function getLastUidByForImapAccountByFolder($folderId)
    {
        $result = $this->query()->select('remote_id')->whereHas('folders', function ($query) use ($folderId) {
            return $query->where('folder_id', $folderId);
        })->orderBy(\DB::raw('CAST(remote_id AS UNSIGNED)'), 'DESC')->first();

        return $result->remote_id ?? null;
    }

    /**
     * Apply where in remote ids
     *
     * @see https://stackoverflow.com/questions/53683774/eloquent-delete-sqlstate22007-invalid-datetime-format-1292-truncated-incor
     *
     * @param  array  $remoteIds
     * @param  null|\Closure  $callback
     * @return static
     */
    public function whereInRemoteIds($remoteIds, $callback = null)
    {
        return $this->scopeQuery(function ($query) use ($remoteIds, $callback) {
            if ($callback) {
                $query = $callback($query);
            }

            return $query->whereIn('remote_id', Arr::valuesAsString($remoteIds));
        });
    }

    /**
     * Batch move messages to a given folder
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $messages
     * @param  int  $to
     * @param  null|int  $from
     * @return void
     */
    public function batchMoveTo($messages, $to, $from = null)
    {
        $messages->loadMissing('folders');

        $allAccounts = resolve(EmailAccountRepository::class)->with('oAuthAccount')->all();
        $allFolders = $this->getFolderRepository()->with('account')->all();
        $to = $allFolders->find($to);

        $messagesByAccount = $messages->groupBy('email_account_id');

        foreach ($messagesByAccount as $accountId => $accountMessages) {
            $messagesByFromFolder = $accountMessages->groupBy(
                fn ($message) => $from ? $message->folders->find($from)->id : $message->folders->first()->id
            )->reject(
                fn ($messages, $fromFolderId) => $allFolders->find($fromFolderId)->is($to) || $to->support_move === false
            );

            if ($messagesByFromFolder->isNotEmpty()) {
                $client = $allAccounts->find($accountId)->getClient();
                $remoteFolders = $client->getFolders();

                // We will use the first message to get the FROM folder
                // as the messages are grouped by FROM, for the rest messages
                // the FROM folder will be the same
                $from = $allFolders->find($messagesByFromFolder->keys()->first());

                foreach ($messagesByFromFolder as $messages) {
                    $maps = $client->batchMoveMessages(
                        $messages->pluck('remote_id')->all(),
                        $remoteFolders->find($to->identifier()),
                        $remoteFolders->find($from->identifier())
                    );

                    foreach ($messages as $message) {
                        // Maps of old => new values exists, in this case, update the current
                        // messages with the new remote_id's to avoid any syncing errors
                        if (is_array($maps)) {
                            // This will help to not delete the message from database
                            // because it's removed
                            if (array_key_exists($message->remote_id, $maps)) {
                                $this->update(['remote_id' => $maps[$message->remote_id]], $message->id);
                            }
                        }

                        // Since messages can belong to multiple folders e.q. for Gmail
                        // We need to remove the FROM folder from the current folders
                        // and push the new folder
                        $message->folders()
                            ->sync(
                                $message->folders->reject(
                                    fn ($folder) => $folder->id == $from->id
                                )->push($to)
                            );
                    }
                }
            }
        }
    }

    /**
     * Parmanently delete given messages
     *
     * @param \Illuminate\Support\Collection
     * @return void
     */
    public function batchDelete($messages)
    {
        $allAccounts = resolve(EmailAccountRepository::class)->all();
        $messagesByAccount = $messages->groupBy('email_account_id');

        $messagesByAccount->each(function ($messages, $accountId) use ($allAccounts) {
            $account = $allAccounts->find($accountId);
            $client = $account->getClient();
            $client->setTrashFolder($client->getFolders()->find($account->trashFolder->identifier()))
                ->batchDeleteMessages($messages->pluck('remote_id')->all());

            $messages->each(function ($message) {
                $this->delete($message->id);
            });
        });
    }

    /**
     * Mark a message as read
     *
     * @param  int  $id
     * @param  int|null  $folderId
     * @return bool
     */
    public function markAsRead($id, $folderId = null)
    {
        $message = $this->find($id);

        if ($message->is_read) {
            return false;
        }

        $folders = $folderId ?
        [$this->getFolderRepository()->with('account')->find($folderId)] :
        $message->folders->loadMissing('account');

        foreach ($folders as $folder) {
            $message->account->createClient()->getMessage(
                $message->remote_id,
                $folder->identifier()
            )->markAsRead();
        }

        return $this->update(['is_read' => true], $id);
    }

    /**
     * Mark a message as unread
     *
     * @param  int  $id
     * @param  int|null  $folderId
     * @return bool
     */
    public function markAsUnread($id, $folderId)
    {
        $message = $this->find($id);

        if (! $message->is_read) {
            return false;
        }

        $folders = $folderId ?
        [$this->getFolderRepository()->with('account')->find($folderId)] :
        $message->folders->loadMissing('account');

        foreach ($folders as $folder) {
            $message->account->createClient()->getMessage(
                $message->remote_id,
                $folder->identifier()
            )->markAsUnread();
        }

        return $this->update(['is_read' => false], $id);
    }

    /**
     * Batch mark a messages as read
     *
     * @param  \Illuminate\Support\Collection  $messages
     * @param  int  $accountId
     * @param  int  $folderId
     * @return void
     */
    public function batchMarkAsRead($messages, $accountId, $folderId)
    {
        $account = resolve(EmailAccountRepository::class)->find($accountId);

        $messages = $messages->reject(fn ($message) => $message->is_read === true)->values();

        if ($messages->isEmpty()) {
            return;
        }

        $account->createClient()->batchMarkAsRead(
            $messages->pluck('remote_id')->all(),
            $this->getFolderRepository()->find($folderId)->identifier()
        );

        $this->scopeQuery(
            fn ($query) => $query->whereIn('id', $messages->pluck('id')->all())
        )->massUpdate(['is_read' => true]);

        $this->resetScope();
    }

    /**
     * Mark a message as read
     *
     * @param  \Illuminate\Support\Collection  $messages
     * @param  int  $accountId
     * @param  int  $folderId
     * @return bool
     */
    public function batchMarkAsUnread($messages, $accountId, $folderId)
    {
        $account = resolve(EmailAccountRepository::class)->find($accountId);

        $messages = $messages->reject(fn ($message) => $message->is_read === false)->values();

        if ($messages->isEmpty()) {
            return;
        }

        $account->createClient()->batchMarkAsUnread(
            $messages->pluck('remote_id')->all(),
            $this->getFolderRepository()->find($folderId)->identifier()
        );

        $this->scopeQuery(
            fn ($query) => $query->whereIn('id', $messages->pluck('id')->all())
        )->massUpdate(['is_read' => false]);

        $this->resetScope();
    }

    /** Persistency Start */
    /**
     * Create the message addresses
     *
     * @param  array  $data
     * @param  \App\Models\EmailAccountMessage  $message
     * @return void
     */
    protected function persistAddresses($data, $message)
    {
        // Delete the existing addresses
        // Below we will re-create them
        $message->addresses()->delete();

        foreach ($this->addresses as $type) {
            if (is_null($data[$type])) {
                continue;
            }

            $this->createAddresses($message, $data[$type], $type);
        }
    }

    /**
     * Create message addresses
     *
     * @param  \App\Models\EmailAccountMessage  $message
     * @param  \App\Innoclapps\Mail\Headers\AddressHeader  $addresses
     * @param  string  $type
     * @return void
     */
    protected function createAddresses($message, $addresses, $type)
    {
        foreach ($addresses->getAll() as $address) {
            $message->addresses()->create(array_merge($address, [
                'address_type' => $type,
            ]));
        }
    }

    /**
     * Persist the message header in database
     *
     * @param \App\Innoclapps\Contracts\MailClient\MessageInterface
     * @param  \App\Models\EmailAccountMessage  $dbMessage
     * @return void
     */
    protected function persistHeaders($message, $dbMessage)
    {
        if ($inReplyTO = $message->getHeader('in-reply-to')) {
            $dbMessage->headers()->updateOrCreate([
                'name' => 'in-reply-to',
            ], [
                'name' => 'in-reply-to',
                'value' => $inReplyTO->getValue(),
                'header_type' => $inReplyTO::class,
            ]);
        }

        if ($references = $message->getHeader('references')) {
            $dbMessage->headers()->updateOrCreate([
                'name' => 'references',
            ], [
                'name' => 'references',
                'value' => implode(', ', $references->getIds()),
                'header_type' => $references::class,
            ]);
        }
    }
    /** Persistency End */
}
