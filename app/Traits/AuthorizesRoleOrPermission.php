<?php

namespace App\Traits;

trait AuthorizesRoleOrPermission
{
    /**
     * Authorize that the current authenticated user has at least one of the given roles.
     * Super Admin always bypasses all checks.
     *
     * @param array|string $roles
     * @param string $message
     * @return void
     */
    protected function authorizeRoles(array|string $roles, string $message = 'Akses ditolak: Anda tidak memiliki wewenang untuk tindakan ini.'): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, $message);
        }

        if ($user->hasRole('Super Admin')) {
            return;
        }

        $roles = (array) $roles;

        if (!$user->hasAnyRole($roles)) {
            abort(403, $message);
        }
    }
}
