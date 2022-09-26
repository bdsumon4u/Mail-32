<?php

namespace App\Innoclapps\Mail\Headers;

use Illuminate\Contracts\Support\Arrayable;

class Header implements Arrayable
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * Initialize header
     *
     * @param  string  $name
     * @param  mixed  $value
     */
    public function __construct($name, $value)
    {
        $this->name = strtolower(trim($name));
        $this->value = is_string($value) ? trim($value) : $value;
    }

    /**
     * Get the header name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the header value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'value' => $this->getValue(),
        ];
    }
}
