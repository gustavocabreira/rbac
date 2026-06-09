<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac;

use GustavoCabreira\Rbac\Contracts\Permissionable;
use GustavoCabreira\Rbac\Support\Resource;

class CompanyAgent
{
    public function __construct(
        private int $id,
        private int $companyId
    ) {}

    public function assign(
        string $rbacRole,
        Permissionable $resource,
        ?int $grantedBy = null
    ): void {
        Rbac::access()->assignAgentRole(
            $this->id,
            Resource::from($resource),
            $rbacRole,
            $grantedBy
        );
    }

    public function revoke(Permissionable $resource): void
    {
        Rbac::access()->removeAgentRole($this->id, Resource::from($resource));
    }

    public function can(string $permission, Permissionable $resource): bool
    {
        $parts = explode('.', $permission);

        if (count($parts) === 1) {
            $permission = $resource->rbacModuleSlug() . '.' . $permission;
        }

        return Rbac::resolver()->can(
            $this->id,
            $this->companyId,
            $permission,
            $resource->rbacResourceId()
        );
    }

    public function hasRole(string $rbacRole, Permissionable $resource): bool
    {
        return $this->role($resource) === $rbacRole;
    }

    public function role(Permissionable $resource): ?string
    {
        return Rbac::access()->getEffectiveRole($this->id, Resource::from($resource));
    }

    /** @return array<string> */
    public function permissions(Permissionable $resource): array
    {
        return Rbac::resolver()->resolve(
            $this->id,
            $this->companyId,
            $resource->rbacModuleSlug(),
            $resource->rbacResourceId()
        );
    }

    /** @return array<int> */
    public function accessibleIds(string $moduleSlug): array
    {
        return Rbac::resolver()->accessibleResourceIds($this->id, $this->companyId, $moduleSlug);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }
}
