# Referência da API

## Rbac (facade estática)

```php
Rbac::configure(PDO $pdo): void
Rbac::forCompany(int $companyId): void
Rbac::company(): int                        // lança RuntimeException se não definido
Rbac::agent(int $id): CompanyAgent
Rbac::role(int $id): HuggyRole
Rbac::resource(string $module, int $id): Resource
Rbac::resolver(): PermissionResolver        // lança se não configurado
Rbac::access(): AccessManager              // lança se não configurado
Rbac::reset(): void                         // isolamento de testes
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

## Classes de Suporte

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

## Exceções

- `GustavoCabreira\Rbac\Exceptions\ModuleNotFoundException` — slug do módulo não encontrado
- `GustavoCabreira\Rbac\Exceptions\ModuleRoleNotFoundException` — slug do role não encontrado
- `RuntimeException` — chamado antes de `configure()` ou `forCompany()`
