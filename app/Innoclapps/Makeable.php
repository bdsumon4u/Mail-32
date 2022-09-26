<?php

namespace App\Innoclapps;

trait Makeable
{
    /**
     * Create new instance
     *
     * @param  array  $params
     * @return static
     */
    final public static function make(...$params): static
    {
        /** @phpstan-ignore-next-line */
        return new static(...$params);
    }
}
