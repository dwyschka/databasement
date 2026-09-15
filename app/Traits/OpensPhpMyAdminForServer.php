<?php

namespace App\Traits;

use App\Models\DatabaseServer;
use App\Models\User;

/**
 * Authorizes the Adminer gate (shared with the "Browse" feature generally),
 * checks the server supports linking out to phpMyAdmin, and dispatches the
 * {@code open-phpmyadmin-modal} event with the pre-filled URL and the
 * credentials to paste in. Requires
 * {@see \Illuminate\Foundation\Auth\Access\AuthorizesRequests}.
 */
trait OpensPhpMyAdminForServer
{
    protected function openPhpMyAdminForServer(DatabaseServer $server): void
    {
        $this->authorize('adminer', DatabaseServer::class);
        abort_unless($server->supportsPhpMyAdmin(), 403);

        $user = auth()->user();
        $useDemoCreds = $user instanceof User && $user->isDemo();

        $this->dispatch('open-phpmyadmin-modal',
            serverName: $server->name,
            databaseIcon: $server->database_type->icon(),
            databaseType: $server->database_type->label(),
            phpmyadminUrl: (string) $server->buildPhpMyAdminUrl(),
            username: $useDemoCreds
                ? (string) config('services.adminer.demo_username')
                : ($server->username ?? ''),
            password: $useDemoCreds
                ? (string) config('services.adminer.demo_password')
                : $server->getDecryptedPassword(),
        );
    }
}
