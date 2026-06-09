<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac\Concerns;

use GustavoCabreira\Rbac\CompanyAgent;
use GustavoCabreira\Rbac\HuggyRole;
use GustavoCabreira\Rbac\Rbac;
use GustavoCabreira\Rbac\Support\Resource;

trait IsPermissionable
{
    public function rbacModuleSlug(): string
    {
        return strtolower((new \ReflectionClass($this))->getShortName());
    }

    public function rbacResourceId(): int
    {
        return (int) $this->id;
    }

    public function grant(object $grantee, string $rbacRole, ?int $grantedBy = null): void
    {
        if ($grantee instanceof CompanyAgent) {
            $grantee->assign($rbacRole, $this, $grantedBy);
        } elseif ($grantee instanceof HuggyRole) {
            $grantee->assign($rbacRole, $this, $grantedBy);
        }
    }

    public function revoke(object $grantee): void
    {
        if ($grantee instanceof CompanyAgent) {
            $grantee->revoke($this);
        } elseif ($grantee instanceof HuggyRole) {
            $grantee->revoke($this);
        }
    }

    public function allows(CompanyAgent $agent, string $permission): bool
    {
        return $agent->can($permission, $this);
    }

    /** @return array<array{agent_id:int,role:?string}> */
    public function agents(): array
    {
        return Rbac::access()->agentsOnResource(Resource::from($this));
    }

    /** @return array<array{role_id:int,role:?string,name:string,description:string}> */
    public function roles(): array
    {
        return Rbac::access()->roleGroupsOnResource(Resource::from($this));
    }

    /** @param array<int> $ids */
    abstract protected static function rbacQueryByIds(array $ids): iterable;

    public static function accessibleFor(int $agentId, int $companyId): iterable
    {
        return static::rbacQueryByIds(static::accessibleIdsFor($agentId, $companyId));
    }

    /** @return array<int> */
    public static function accessibleIdsFor(int $agentId, int $companyId): array
    {
        return Rbac::resolver()->accessibleResourceIds(
            $agentId,
            $companyId,
            (new static())->rbacModuleSlug()
        );
    }
}
