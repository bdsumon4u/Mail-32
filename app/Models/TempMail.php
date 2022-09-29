<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempMail extends Model
{
    use HasFactory;

    protected $fillable = [
        'address', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * A temp email account can belongs to an email account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<EmailAccount, TempMail>
     */
    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }
}
