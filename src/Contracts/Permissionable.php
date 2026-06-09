<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac\Contracts;

interface Permissionable
{
    public function rbacModuleSlug(): string;
    public function rbacResourceId(): int;
}
