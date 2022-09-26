<?php

namespace App\Models;

use App\Support\EmailAccountMessageBody;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
}
