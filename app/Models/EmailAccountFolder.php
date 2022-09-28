<?php

namespace App\Models;

use App\Enums\ConnectionType;
use App\Innoclapps\MailClient\FolderIdentifier;
use App\Mota\HasMeta;
use App\Mota\Metable;
use App\Support\EmailAccountFolderCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmailAccountFolder extends Model implements Metable
{
    use HasFactory;
    use HasMeta;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id', 'name', 'display_name', 'remote_id', 'email_account_id', 'syncable', 'selectable', 'type', 'support_move',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'selectable' => 'boolean',
        'syncable' => 'boolean',
        'support_move' => 'boolean',
        'parent_id' => 'int',
        'email_account_id' => 'int',
    ];

    /**
     * A folder belongs to email account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<EmailAccount, EmailAccountFolder>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'email_account_id');
    }

    /**
     * A folder belongs to email account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<EmailAccountMessage>
     */
    public function messages(): BelongsToMany
    {
        return $this->belongsToMany(
            EmailAccountMessage::class,
            'email_account_message_folders',
            'folder_id',
            'message_id'
        );
    }

    /**
     * Get the folder identifier
     *
     * @return \App\Innoclapps\MailClient\FolderIdentifier
     */
    public function identifier(): FolderIdentifier
    {
        if ($this->account->connection_type === ConnectionType::Imap) {
            return new FolderIdentifier('name', $this->name);
        }

        return new FolderIdentifier('id', $this->remote_id);
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return (new EmailAccountFolderCollection($models))->sortByType();
    }

    // REPOSITORY #

    /**
     * Mark the folder as not selectable and syncable
     *
     * @param  int  $id
     * @return void
     */
    public function markAsNotSelectable(int $id)
    {
        $this->query()->where('id', $id)->update(['syncable' => false, 'selectable' => false]);
    }

    /**
     * Count the total unread messages for a given folder
     *
     * @param  int  $folderId
     * @return int
     */
    public function countUnreadMessages(int $folderId): int
    {
        return $this->countReadOrUnreadMessages($folderId, 0);
    }

    /**
     * Count the total read messages for a given folder
     *
     * @param  int  $folderId
     * @return int
     */
    public function countReadMessages(int $folderId): int
    {
        return $this->countReadOrUnreadMessages($folderId, 1);
    }

    /**
     * Count read or unread messages for a given folder
     *
     * @param  int  $folderId
     * @param  int  $isRead
     * @return int
     */
    protected function countReadOrUnreadMessages($folderId, $isRead)
    {
        return $this->query()
            ->select(['id'])
            ->withCount(['messages' => function ($query) use ($isRead) {
                return $query->where('is_read', $isRead);
            }])->where(['id' => $folderId])->first()->messages_count ?? 0;
    }
}
