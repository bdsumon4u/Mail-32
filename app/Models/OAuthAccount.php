<?php

namespace App\Models;

use App\Innoclapps\Facades\Google;
use App\Innoclapps\OAuth\AccessTokenProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OAuthAccount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_accounts';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requires_auth' => 'boolean',
        'access_token' => 'encrypted',
        'user_id' => 'int',
    ];

    /**
     * Boot the OAuthAccount Model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * Handle the deleted event
         */
        static::deleted(function ($account) {
            if ($account->type === 'google') {
                try {
                    Google::revokeToken($account->access_token);
                } catch (\Exception $e) {
                }
            }
        });
    }

    /**
     * Create new token provider
     *
     * @return \App\Innoclapps\OAuth\AccessTokenProvider
     */
    public function tokenProvider(): AccessTokenProvider
    {
        return new AccessTokenProvider($this->access_token, $this->email);
    }

    /**
     * Set that this account requires authentication
     *
     * @param  bool  $value
     */
    public function setRequiresAuthentication($value = true)
    {
        $this->update(['requires_auth' => $value]);
    }
}
