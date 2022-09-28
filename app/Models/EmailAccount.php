<?php

namespace App\Models;

use App\Enums\ConnectionType;
use App\Enums\EmailAccountType;
use App\Enums\SyncState;
use App\Innoclapps\MailClient\Client;
use App\Innoclapps\MailClient\ClientManager;
use App\Innoclapps\MailClient\FolderCollection;
use App\Mota\HasMeta;
use App\Mota\Metable;
use App\Support\Concerns\HasImap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmailAccount extends Model implements Metable
{
    use HasFactory;
    use HasMeta;
    use HasImap;

    /**
     * Indicates the primary meta key for user
     */
    const PRIMARY_META_KEY = 'primary-email-account';

    /**
     * Client instance cached property
     *
     * @var \App\Innoclapps\MailClient\Client
     */
    protected $client;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'create_contact' => 'boolean',
        'initial_sync_from' => 'datetime',
        'last_sync_at' => 'datetime',
        'smtp_port' => 'int',
        'imap_port' => 'int',
        'validate_cert' => 'boolean',
        'password' => 'encrypted',
        'sync_state' => SyncState::class,
        'connection_type' => ConnectionType::class,
        'access_token_id' => 'int',
        'sent_folder_id' => 'int',
        'trash_folder_id' => 'int',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email', 'password', 'connection_type',
        'last_sync_at', 'requires_auth', 'initial_sync_from',
        'sent_folder_id', 'trash_folder_id', 'create_contact',
        // imap
        'validate_cert', 'username',
        'imap_server', 'imap_port', 'imap_encryption',
        'smtp_server', 'smtp_port', 'smtp_encryption',
    ];

    /**
     * A model has OAuth connection
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<OAuthAccount>
     */
    public function oAuthAccount(): HasOne
    {
        return $this->hasOne(OAuthAccount::class, 'id', 'o_auth_account_id'); // HOTASH #
    }

    public function scopeSyncable(Builder $query)
    {
        return $query->where('sync_state', SyncState::ENABLED);
    }

    /**
     * Check whether the user disabled the sync
     *
     * @return bool
     */
    public function isSyncDisabled(): bool
    {
        return $this->sync_state === SyncState::DISABLED;
    }

    /**
     * Check whether the system disabled the sync
     *
     * @return bool
     */
    public function isSyncStoppedBySystem(): bool
    {
        return $this->sync_state === SyncState::STOPPED;
    }

    /**
     * Checks whether an initial sync is performed for the syncable
     *
     * @return bool
     */
    public function isInitialSyncPerformed(): bool
    {
        return ! empty($this->last_sync_at);
    }

    /**
     * We will set custom accessor for the requires_auth attribute
     *
     * If it's OAuthAccount we will return the value from the actual oauth account
     * instead of the syncable value, because OAuth account can be used in other features
     * in the application and these features may update the requires_auth on the oauth_accounts table directly
     * In this case, we this will ensure that the requires_auth attribute value is up to date
     *
     * If it's regular account without OAuth e.q. IMAP, we will return the value from the actual syncable model
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function requiresAuth(): Attribute
    {
        return Attribute::get(function ($value) {
            return is_null($this->oAuthAccount) ? (bool) $value : $this->oAuthAccount->requires_auth; // HOTASH #
        });
    }

    /**
     * An email account can belongs to a user (personal)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, EmailAccount>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * An email account has many mail messages
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailAccountMessage>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(EmailAccountMessage::class);
    }

    /**
     * An email account has many mail folders
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmailAccountFolder>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(EmailAccountFolder::class);
    }

    /**
     * An email account has sent folder indicator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<EmailAccountFolder, EmailAccount>
     */
    public function sentFolder(): BelongsTo
    {
        return $this->belongsTo(EmailAccountFolder::class);
    }

    /**
     * An email account has trash folder indicator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<EmailAccountFolder, EmailAccount>
     */
    public function trashFolder(): BelongsTo
    {
        return $this->belongsTo(EmailAccountFolder::class);
    }

    /**
     * Check whether the account is shared
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Check whether the account is personal
     *
     * @return bool
     */
    public function isPersonal(): bool
    {
        return ! $this->isShared();
    }

    /**
     * Get the user type of the account
     *
     * 'shared' or 'personal'
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function type(): Attribute
    {
        return Attribute::get(
            fn () => $this->isShared() ? EmailAccountType::SHARED : EmailAccountType::PERSONAL
        );
    }

    /**
     * Check whether the account is primary account for the current logged in user
     *
     * @return bool
     */
    public function isPrimary(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->getMeta(self::PRIMARY_META_KEY) == $this->id;
    }

    /**
     * Mark the account as primary for the given user
     */
    public function markAsPrimary(Metable & User $user): static
    {
        $user->setMeta(self::PRIMARY_META_KEY, $this->id);

        return $this;
    }

    /**
     * Unmark the account as primary for the given user
     */
    public static function unmarkAsPrimary(Metable & User $user): void
    {
        $user->removeMeta(self::PRIMARY_META_KEY);
    }

    /**
     * Check whether the account can send mails
     *
     * @return bool
     */
    public function canSendMails(): bool
    {
        return ! ($this->requires_auth || $this->isSyncStoppedBySystem());
    }

    /**
     * Create email account mail client
     *
     * @return \App\Innoclapps\MailClient\Client
     */
    public function createClient(): Client
    {
        if ($this->oAuthAccount) {
            return ClientManager::createClient(
                $this->connection_type,
                $this->oAuthAccount->tokenProvider()
            );
        }

        return ClientManager::createClient(
            $this->connection_type,
            $this->getImapConfig(),
            $this->getSmtpConfig()
        );
    }

    /**
     * Get the account client
     *
     * @return \App\Innoclapps\MailClient\Client
     */
    public function getClient(): Client
    {
        if (! $this->client) { // @phpstan-ignore-line
            $this->client = $this->createClient()
                ->setFromName(config('app.name'))
                ->setFromAddress($this->email);
        }

        return $this->client;
    }

    /**
     * Set that this account requires authentication
     *
     * @param  bool  $value
     * @return void
     */
    public function setRequiresAuthentication($value = true)
    {
        if (! is_null($this->oAuthAccount)) {
            $this->oAuthAccount()->update(
                ['requires_auth' => $value],
            );
        }

        $this->update(['requires_auth' => $value]);
    }

    /**
     * Set the account synchronization state
     *
     * @param  \App\Enums\SyncState  $state
     * @param  string|null  $comment
     * @return void
     */
    public function setSyncState(SyncState $state, $comment = null)
    {
        $this->unguarded(function () use ($state, $comment) {
            $this->update([
                'sync_state' => $state,
                'sync_state_comment' => $comment,
            ]);
        });
    }

    public function updateFolders($folders)
    {
        foreach ($folders as $folder) {
            $this->persistForAccount($folder);
        }
    }

    /**
     * Update folder for a given account
     *
     * @param  array  $folder
     * @return \App\Models\EmailAccountFolder
     */
    public function persistForAccount(array $folder)
    {
        $parent = EmailAccountFolder::updateOrCreate(
            $this->getUpdateOrCreateAttributes($this, $folder),
            array_merge($folder, [
                'email_account_id' => $this->id,
                'syncable' => $folder['syncable'] ?? false,
            ])
        );

        $this->handleChildFolders($parent, $folder);

        return $parent;
    }

    /**
     * Handle the child folders creation process
     *
     * @param  \App\Models\EmailAccountFolder  $parentFolder
     * @param  array  $folder
     * @param  \App\Models\EmailAccount  $account
     * @return void
     */
    protected function handleChildFolders($parentFolder, $folder)
    {
        // Avoid errors if the children key is not set
        if (! isset($folder['children'])) {
            return;
        }

        if ($folder['children'] instanceof FolderCollection) {
            /**
             * @see \App\Listeners\CreateEmailAccountViaOAuth
             */
            $folder['children'] = $folder['children']->toArray();
        }

        foreach ($folder['children'] as $child) {
            $parent = $this->persistForAccount(array_merge($child, [
                'parent_id' => $parentFolder->id,
            ]));

            $this->handleChildFolders($parent, $child);
        }
    }

    /**
     * Get the attributes that should be used for update or create method
     *
     * @param  \App\Models\EmailAccount  $account
     * @param  array  $folder
     * @return array
     */
    protected function getUpdateOrCreateAttributes($folder)
    {
        $attributes = ['email_account_id' => $this->id];

        // If the folder database ID is passed
        // use the ID as unique identifier for the folder
        if (isset($folder['id'])) {
            $attributes['id'] = $folder['id'];
        } else {
            // For imap account, we use the name as unique identifier
            // as the remote_id may not always be unique
            if ($this->connection_type === ConnectionType::Imap) {
                $attributes['name'] = $folder['name'];
            } else {
                // For API based accounts e.q. Gmail and Outlook
                // we use the remote_id as unique identifier
                $attributes['remote_id'] = $folder['remote_id'];
            }
        }

        return $attributes;
    }
}
