<?php

namespace App\Mota;

interface Metable
{
    /**
     * Add or update the value of the `Meta` at a given key.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function setMeta(string $key, $value): void;

    /**
     * Check if a `Meta` has been set at a given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasMeta(string $key): bool;

    /**
     * Delete the `Meta` at a given key.
     *
     * @param  string  $key
     * @return void
     */
    public function removeMeta(string $key): void;

    /**
     * Retrieve the value of the `Meta` at a given key.
     *
     * @param  string  $key
     * @param  mixed  $default Fallback value if no Meta is found.
     * @return mixed
     */
    public function getMeta(string $key, $default = null);
}
