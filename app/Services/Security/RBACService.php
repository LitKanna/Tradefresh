<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserRole;
use App\Models\RolePermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class RBACService
{
    protected $cachePrefix = 'rbac_';
    protected $cacheDuration = 3600; // 1 hour

    /**
     * Assign role to user
     */
    public function assignRole(User $user, $role, User $assignedBy = null, array $options = []): UserRole
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        // Check if user already has this role
        $existingRole = UserRole::where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->first();

        if ($existingRole) {
            throw new Exception("User already has the role: {$role->name}");
        }

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_by' => $assignedBy ? $assignedBy->id : null,
            'assigned_at' => now(),
            'expires_at' => $options['expires_at'] ?? null,
            'scope' => $options['scope'] ?? null
        ]);

        $this->clearUserCache($user->id);

        return $userRole;
    }

    /**
     * Revoke role from user
     */
    public function revokeRole(User $user, $role): bool
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
            if (!$role) {
                return false;
            }
        }

        $deleted = UserRole::where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->delete();

        if ($deleted) {
            $this->clearUserCache($user->id);
        }

        return $deleted > 0;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(User $user, $role): bool
    {
        $userRoles = $this->getUserRoles($user);
        
        if (is_string($role)) {
            return $userRoles->contains('name', $role);
        }

        return $userRoles->contains('id', $role->id);
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(User $user, array $roles): bool
    {
        $userRoles = $this->getUserRoles($user);
        
        foreach ($roles as $role) {
            if (is_string($role)) {
                if ($userRoles->contains('name', $role)) {
                    return true;
                }
            } else {
                if ($userRoles->contains('id', $role->id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has all of the specified roles
     */
    public function hasAllRoles(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($user, $role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Grant permission to role
     */
    public function grantPermission(Role $role, $permission, array $conditions = null): RolePermission
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        // Check if role already has this permission
        $existing = RolePermission::where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->first();

        if ($existing) {
            // Update conditions if different
            if ($conditions !== null) {
                $existing->conditions = $conditions;
                $existing->save();
            }
            return $existing;
        }

        $rolePermission = RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
            'conditions' => $conditions
        ]);

        $this->clearRoleCache($role->id);

        return $rolePermission;
    }

    /**
     * Revoke permission from role
     */
    public function revokePermission(Role $role, $permission): bool
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
            if (!$permission) {
                return false;
            }
        }

        $deleted = RolePermission::where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->delete();

        if ($deleted) {
            $this->clearRoleCache($role->id);
        }

        return $deleted > 0;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(User $user, string $permission, array $context = []): bool
    {
        $permissions = $this->getUserPermissions($user);
        
        // Direct permission check
        if (!$permissions->contains('name', $permission)) {
            return false;
        }

        // Check conditions if context provided
        if (!empty($context)) {
            $perm = $permissions->firstWhere('name', $permission);
            if ($perm && $perm->conditions) {
                return $this->evaluateConditions($perm->conditions, $context);
            }
        }

        return true;
    }

    /**
     * Check if user has any of the specified permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the specified permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user can perform action on resource
     */
    public function can(User $user, string $action, string $resource, $resourceInstance = null): bool
    {
        $permission = "{$resource}.{$action}";
        
        $context = [];
        if ($resourceInstance) {
            $context['resource'] = $resourceInstance;
            $context['resource_id'] = $resourceInstance->id ?? null;
            $context['owner_id'] = $resourceInstance->user_id ?? $resourceInstance->owner_id ?? null;
        }

        return $this->hasPermission($user, $permission, $context);
    }

    /**
     * Get all roles for a user
     */
    public function getUserRoles(User $user): Collection
    {
        return Cache::remember(
            $this->cachePrefix . "user_roles_{$user->id}",
            $this->cacheDuration,
            function () use ($user) {
                return $user->roles()
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->get();
            }
        );
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions(User $user): Collection
    {
        return Cache::remember(
            $this->cachePrefix . "user_permissions_{$user->id}",
            $this->cacheDuration,
            function () use ($user) {
                $roleIds = $this->getUserRoles($user)->pluck('id');
                
                if ($roleIds->isEmpty()) {
                    return collect();
                }

                return Permission::join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                    ->whereIn('role_permissions.role_id', $roleIds)
                    ->select('permissions.*', 'role_permissions.conditions')
                    ->distinct()
                    ->get();
            }
        );
    }

    /**
     * Create a new role
     */
    public function createRole(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'] ?? null,
            'is_system' => $data['is_system'] ?? false,
            'priority' => $data['priority'] ?? 0
        ]);

        // Assign permissions if provided
        if (isset($data['permission_ids'])) {
            foreach ($data['permission_ids'] as $permissionId) {
                RolePermission::create([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId
                ]);
            }
        }

        return $role;
    }

    /**
     * Create a new permission
     */
    public function createPermission(array $data): Permission
    {
        return Permission::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'resource' => $data['resource'] ?? null,
            'action' => $data['action'] ?? null,
            'conditions' => $data['conditions'] ?? null,
            'is_system' => $data['is_system'] ?? false
        ]);
    }

    /**
     * Evaluate permission conditions
     */
    protected function evaluateConditions(array $conditions, array $context): bool
    {
        foreach ($conditions as $key => $value) {
            switch ($key) {
                case 'owner_only':
                    if ($value && isset($context['owner_id'])) {
                        $user = $context['user'] ?? null;
                        if (!$user || $user->id !== $context['owner_id']) {
                            return false;
                        }
                    }
                    break;

                case 'department':
                    if (isset($context['user'])) {
                        $userDept = $context['user']->department ?? null;
                        if ($userDept !== $value) {
                            return false;
                        }
                    }
                    break;

                case 'time_range':
                    $now = now();
                    if (isset($value['start']) && $now->lt(Carbon::parse($value['start']))) {
                        return false;
                    }
                    if (isset($value['end']) && $now->gt(Carbon::parse($value['end']))) {
                        return false;
                    }
                    break;

                case 'ip_whitelist':
                    $clientIp = request()->ip();
                    if (!in_array($clientIp, $value)) {
                        return false;
                    }
                    break;

                case 'max_amount':
                    if (isset($context['amount']) && $context['amount'] > $value) {
                        return false;
                    }
                    break;

                default:
                    // Custom condition evaluation
                    if (isset($context[$key]) && $context[$key] !== $value) {
                        return false;
                    }
            }
        }

        return true;
    }

    /**
     * Clear user cache
     */
    protected function clearUserCache(int $userId): void
    {
        Cache::forget($this->cachePrefix . "user_roles_{$userId}");
        Cache::forget($this->cachePrefix . "user_permissions_{$userId}");
    }

    /**
     * Clear role cache
     */
    protected function clearRoleCache(int $roleId): void
    {
        // Clear cache for all users with this role
        $userIds = UserRole::where('role_id', $roleId)->pluck('user_id');
        foreach ($userIds as $userId) {
            $this->clearUserCache($userId);
        }
    }

    /**
     * Get role hierarchy
     */
    public function getRoleHierarchy(): array
    {
        $roles = Role::orderBy('priority', 'desc')->get();
        
        $hierarchy = [];
        foreach ($roles as $role) {
            $hierarchy[] = [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'priority' => $role->priority,
                'permissions_count' => $role->permissions()->count(),
                'users_count' => $role->users()->count()
            ];
        }

        return $hierarchy;
    }

    /**
     * Sync role permissions
     */
    public function syncPermissions(Role $role, array $permissionIds): void
    {
        DB::transaction(function () use ($role, $permissionIds) {
            // Remove existing permissions
            RolePermission::where('role_id', $role->id)->delete();

            // Add new permissions
            foreach ($permissionIds as $permissionId) {
                RolePermission::create([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId
                ]);
            }

            $this->clearRoleCache($role->id);
        });
    }

    /**
     * Clone role with permissions
     */
    public function cloneRole(Role $role, string $newName, string $newDisplayName): Role
    {
        $newRole = Role::create([
            'name' => $newName,
            'display_name' => $newDisplayName,
            'description' => $role->description,
            'permissions' => $role->permissions,
            'is_system' => false,
            'priority' => $role->priority
        ]);

        // Copy permissions
        $permissions = RolePermission::where('role_id', $role->id)->get();
        foreach ($permissions as $permission) {
            RolePermission::create([
                'role_id' => $newRole->id,
                'permission_id' => $permission->permission_id,
                'conditions' => $permission->conditions
            ]);
        }

        return $newRole;
    }

    /**
     * Get permission matrix for all roles
     */
    public function getPermissionMatrix(): array
    {
        $roles = Role::all();
        $permissions = Permission::orderBy('category')->orderBy('name')->get();
        
        $matrix = [];
        foreach ($permissions as $permission) {
            $row = [
                'permission' => $permission->name,
                'display_name' => $permission->display_name,
                'category' => $permission->category,
                'roles' => []
            ];

            foreach ($roles as $role) {
                $hasPermission = RolePermission::where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->exists();
                
                $row['roles'][$role->name] = $hasPermission;
            }

            $matrix[] = $row;
        }

        return $matrix;
    }
}