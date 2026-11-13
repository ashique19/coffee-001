<?php

namespace App\Services\Operations;

use App\Models\BusinessSetting;
use App\Models\BusinessSettingUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StaffAdminService
{
    /**
     * @param  list<string>  $roles
     */
    public function createUser(
        Tenant $tenant,
        string $name,
        string $email,
        string $password,
        ?int $branchId,
        array $roles,
    ): StaffUser {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        if (StaffUser::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => [__('operations.email_taken')]]);
        }

        $user = StaffUser::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branchId,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->syncRoles($tenant, $user, $roles);

        return $user;
    }

    /**
     * @param  list<string>  $roles
     */
    public function syncRoles(Tenant $tenant, StaffUser $user, array $roles): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $allowed = ['owner', 'manager', 'cashier', 'accountant'];
        $roles = array_values(array_intersect($roles, $allowed));

        if ($roles === []) {
            throw ValidationException::withMessages(['roles' => [__('operations.role_required')]]);
        }

        if ($user->hasRole('owner') && ! in_array('owner', $roles, true)) {
            $ownerCount = StaffUser::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'owner'))
                ->count();
            if ($ownerCount <= 1) {
                throw ValidationException::withMessages(['roles' => [__('operations.last_owner')]]);
            }
        }

        $user->syncRoles($roles);
    }

    public function setActive(StaffUser $user, bool $active): void
    {
        if (! $active && $user->hasRole('owner')) {
            $ownerCount = StaffUser::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->whereHas('roles', fn ($q) => $q->where('name', 'owner'))
                ->count();
            if ($ownerCount <= 1) {
                throw ValidationException::withMessages(['is_active' => [__('operations.last_owner')]]);
            }
        }

        $user->update(['is_active' => $active]);
    }
}
