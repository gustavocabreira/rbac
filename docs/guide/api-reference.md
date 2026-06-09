# API Reference

## Rbac (static facade)

```php
Rbac::configure(PDO $pdo): void
Rbac::forCompany(int $companyId): void
Rbac::company(): int                        // throws RuntimeException if not set
Rbac::agent(int $id): CompanyAgent
Rbac::role(int $id): HuggyRole
Rbac::resource(string $module, int $id): Resource
Rbac::resolver(): PermissionResolver        // throws if not configured
Rbac::access(): AccessManager              // throws if not configured
Rbac::reset(): void                         // test isolation
```

## CompanyAgent

```php
$agent->assign(string $rbacRole, Permissionable $resource, ?int $grantedBy = null): void
$agent->revoke(Permissionable $resource): void
$agent->can(string $permission, Permissionable $resource): bool
$agent->hasRole(string $rbacRole, Permissionable $resource): bool
$agent->role(Permissionable $resource): ?string
$agent->permissions(Permissionable $resource): array
$agent->accessibleIds(string $moduleSlug): array
```

## HuggyRole

```php
$role->assign(string $rbacRole, Permissionable $resource, ?int $grantedBy = null): void
$role->revoke(Permissionable $resource): void
$role->hasRole(string $rbacRole, Permissionable $resource): bool
$role->resourcesWith(string $moduleSlug): array
```

## PermissionResolver

```php
$resolver->resolveLevel(int $agentId, int $companyId, string $moduleSlug, int $resourceId): int
$resolver->resolve(int $agentId, int $companyId, string $moduleSlug, int $resourceId): array
$resolver->can(int $agentId, int $companyId, string $permission, int $resourceId): bool
$resolver->permissionsForLevel(int $level, string $moduleSlug): array
$resolver->accessibleResourceIds(int $agentId, int $companyId, string $moduleSlug): array
$resolver->accessibleResources(int $agentId, int $companyId): array  // ['board' => [1,5]]
$resolver->flush(): void
```

## AccessManager

```php
$access->assignAgentRole(int $agentId, Resource $r, string $roleSlug, ?int $grantedBy = null): void
$access->removeAgentRole(int $agentId, Resource $r): void
$access->assignRoleGroupRole(int $roleId, Resource $r, string $roleSlug, ?int $grantedBy = null): void
$access->removeRoleGroupRole(int $roleId, Resource $r): void
$access->getEffectiveRole(int $agentId, Resource $r): ?string
$access->agentsOnResource(Resource $r): array         // [['agent_id' => int, 'role' => ?string]]
$access->roleGroupsOnResource(Resource $r): array     // [['role_id','role','name','description']]
$access->rolesOn(int $agentId, Resource $r): array
$access->resourcesWithRole(int $roleId, string $moduleSlug): array
```

## Support Classes

```php
Level::OWNER    // 3
Level::ADMIN    // 2
Level::VIEWER   // 1
Level::NONE     // 0

RoleSlug::OWNER   // 'owner'
RoleSlug::ADMIN   // 'admin'
RoleSlug::VIEWER  // 'viewer'

Resource::from(Permissionable $p): Resource
```

## Exceptions

- `Huggy\Rbac\Exceptions\ModuleNotFoundException` — module slug not found
- `Huggy\Rbac\Exceptions\ModuleRoleNotFoundException` — role slug not found
- `RuntimeException` — called before `configure()` or `forCompany()`
