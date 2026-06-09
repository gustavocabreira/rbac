<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac\Support;

use GustavoCabreira\Rbac\Contracts\Permissionable;

final class Resource implements Permissionable
{
    public function __construct(
        private string $moduleSlug,
        private int $resourceId
    ) {}

    public function rbacModuleSlug(): string
    {
        return $this->moduleSlug;
    }

    public function rbacResourceId(): int
    {
        return $this->resourceId;
    }

    public static function from(Permissionable $p): self
    {
        return new self($p->rbacModuleSlug(), $p->rbacResourceId());
    }
}
