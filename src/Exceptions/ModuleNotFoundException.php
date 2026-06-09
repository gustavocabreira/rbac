<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac\Exceptions;

use RuntimeException;

class ModuleNotFoundException extends RuntimeException
{
    public static function forSlug(string $slug): self
    {
        return new self("Module not found: {$slug}");
    }
}
