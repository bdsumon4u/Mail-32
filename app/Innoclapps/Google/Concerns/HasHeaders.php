<?php

namespace App\Innoclapps\Google\Concerns;

trait HasHeaders
{
    /**
     * @var \App\Innoclapps\Mail\Headers\HeadersCollection
     */
    protected $headers;

    /**
     * Get all headers for the configured part
     *
     * @return \App\Innoclapps\Mail\Headers\HeadersCollection
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get single header value
     *
     * @return \App\Innoclapps\Mail\Headers\Header|null
     */
    public function getHeader($name)
    {
        return $this->headers->find($name);
    }

    /**
     * Get single header value
     *
     * @return string|null
     */
    public function getHeaderValue($name)
    {
        $header = $this->getHeader($name); // HOTASH #

        return $header ? $header->getValue() : null;
    }
}
