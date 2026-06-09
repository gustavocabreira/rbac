<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac;

use GustavoCabreira\Rbac\Contracts\Permissionable;
use GustavoCabreira\Rbac\Support\Resource;

class HuggyRole
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
        Rbac::access()->assignRoleGroupRole(
            $this->id,
            Resource::from($resource),
            $rbacRole,
            $grantedBy
        );
    }

    public function revoke(Permissionable $resource): void
    {
        Rbac::access()->removeRoleGroupRole($this->id, Resource::from($resource));
    }

    public function hasRole(string $rbacRole, Permissionable $resource): bool
    {
        $groups = Rbac::access()->roleGroupsOnResource(Resource::from($resource));

        foreach ($groups as $group) {
            if ($group['role_id'] === $this->id && $group['role'] === $rbacRole) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int> */
    public function resourcesWith(string $moduleSlug): array
    {
        return Rbac::access()->resourcesWithRole($this->id, $moduleSlug);
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
