<?php

namespace App\Auth;

use App\Support\PlatformPermissionTeam;
use App\Business\BusinessContext;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

class PermissionTenantTeamResolver implements PermissionsTeamResolver
{
    public int|string|null $teamId = null;

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->teamId !== null) {
            return $this->teamId;
        }

        if (Auth::guard('platform')->check() && ! Auth::guard('tenant')->check()) {
            return PlatformPermissionTeam::TENANT_ID;
        }

        return BusinessContext::id();
    }

    public function setPermissionsTeamId(int|string|\Illuminate\Database\Eloquent\Model|null $id): void
    {
        $this->teamId = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->getKey() : $id;
    }
}
