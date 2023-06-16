<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Stash;

use Stash\Driver\Composite;

/**
 * WordPress has a 'wp_cache_flush_runtime' function to clear only non-persistent caches.
 * Stash's default composite does not differentiate between persistent and non-persistent
 * drivers, so we extend it to add a little extra API surface
 * @see wp_cache_flush_runtime
 */
class PersistenceAwareComposite extends Composite
{
    public function clearNonPersistent(): void
    {
        foreach ($this->drivers as $driver) {
            if (!$driver->isPersistent()) {
                $driver->clear();
            }
        }
    }
}
