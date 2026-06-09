<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac\Tests\Support;

use GustavoCabreira\Rbac\Connection;
use GustavoCabreira\Rbac\Database\LocalMigrator;
use PDO;

class TestDatabase
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host   = getenv('DB_HOST')     ?: 'mysql';
        $dbname = getenv('DB_DATABASE') ?: 'rbac_test';
        $user   = getenv('DB_USERNAME') ?: 'root';
        $pass   = getenv('DB_PASSWORD') ?: 'secret';

        self::$pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
            ]
        );

        return self::$pdo;
    }

    public static function setup(): void
    {
        $migrator = new LocalMigrator(self::pdo());
        $migrator->rollback();
        $migrator->migrate();
    }

    public static function connection(): Connection
    {
        return new Connection(self::pdo());
    }

    public static function beginTransaction(): void
    {
        self::pdo()->beginTransaction();
    }

    public static function rollback(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }

    public static function truncateTables(): void
    {
        $pdo = self::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'tb_Companies_Agents',
            'tb_Resource_Agent_Access',
            'tb_Resource_Role_Access',
            'tb_Roles',
            'tb_Module_Role_Permissions',
            'tb_Permissions',
            'tb_Module_Roles',
            'tb_Modules',
        ];

        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public static function insertCompanyAgent(int $companyId, int $agentId, int $agentRole): void
    {
        self::connection()->statement(
            'INSERT INTO tb_Companies_Agents
               (coaCompaniesAgentsCompanyID, coaCompaniesAgentsAgentID, coaCompaniesAgentsAgentRole)
             VALUES (?, ?, ?)',
            [$companyId, $agentId, $agentRole]
        );
    }
}
