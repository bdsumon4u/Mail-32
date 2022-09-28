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
     * @return \Illuminate\Collections\LazyCollection
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
     * @return \Illuminate\Collections\LazyCollection
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
        // $this->handleAttachments($dbMessage, $message); // HOTASH #

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
     * Update a message for a given account
     *
     * NOTE: This functions does not syncs attachments
     *
     * @param  \App\Innoclapps\MailClient\AbstractMessage  $message
     * @param  int  $id The account ID
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
     * @param  \App\EmailAcccountMessage  $dbMessage
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
