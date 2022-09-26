<?php

namespace App\Models;

use App\Enums\ConnectionType;
use App\Enums\EmailAccountType;
use App\Enums\SyncState;
use App\Innoclapps\MailClient\Client;
use App\Innoclapps\MailClient\ClientManager;
use App\Mota\HasMeta;
use App\Mota\Metable;
use App\Support\Concerns\HasImap;
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
}
