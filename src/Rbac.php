<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac;

use PDO;
use RuntimeException;
use GustavoCabreira\Rbac\Support\Resource;

final class Rbac
{
    private static ?Connection $connection = null;
    private static ?PermissionResolver $resolver = null;
    private static ?AccessManager $access = null;
    private static ?int $companyId = null;

    public static function configure(PDO $pdo): void
    {
        self::$connection = new Connection($pdo);
        self::$resolver   = new PermissionResolver(self::$connection);
        self::$access     = new AccessManager(self::$connection, self::$resolver);
    }

    public static function forCompany(int $companyId): void
    {
        self::$companyId = $companyId;
    }

    public static function company(): int
    {
        if (self::$companyId === null) {
            throw new RuntimeException('Company context not set. Call Rbac::forCompany() first.');
        }

        return self::$companyId;
    }

    public static function agent(int $id): CompanyAgent
    {
        return new CompanyAgent($id, self::company());
    }

    public static function role(int $id): HuggyRole
    {
        return new HuggyRole($id, self::company());
    }

    public static function resource(string $module, int $id): Resource
    {
        return new Resource($module, $id);
    }

    public static function resolver(): PermissionResolver
    {
        if (self::$resolver === null) {
            throw new RuntimeException('Rbac not configured. Call Rbac::configure() first.');
        }

        return self::$resolver;
    }

    public static function access(): AccessManager
    {
        if (self::$access === null) {
            throw new RuntimeException('Rbac not configured. Call Rbac::configure() first.');
        }

        return self::$access;
    }

    public static function reset(): void
    {
        self::$connection = null;
        self::$resolver   = null;
        self::$access     = null;
        self::$companyId  = null;
    }
}
