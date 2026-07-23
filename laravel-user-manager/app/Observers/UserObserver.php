<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Invalidate every cached page after either create or update.
     */
    public function saved(User $user): void
    {
        $this->invalidateIndexCache();
    }

    /**
     * Invalidate every cached page after delete.
     */
    public function deleted(User $user): void
    {
        $this->invalidateIndexCache();
    }

    /**
     * Versioned keys avoid backend-specific cache-tag requirements.
     */
    private function invalidateIndexCache(): void
    {
        Cache::forever('users.cache.version', (string) Str::uuid());
    }
}
