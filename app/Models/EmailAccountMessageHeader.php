<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class EmailAccountMessageHeader extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model has timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'value', 'header_type'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'message_id' => 'int',
    ];

    /**
     * Get the mapped attribute
     *
     * We will map the header into a appropriate header class
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function mapped(): Attribute
    {
        return Attribute::get(fn () => new $this->header_type($this->name, $this->value));
    }
}
