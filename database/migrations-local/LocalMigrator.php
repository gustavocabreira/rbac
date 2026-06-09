<?php

declare(strict_types=1);

// LOCAL/TEST ONLY — production uses the legacy schema. Never run this in production.

namespace GustavoCabreira\Rbac\Database;

use PDO;

class LocalMigrator
{
    public function __construct(private PDO $pdo) {}

    public function migrate(): void
    {
        $sql = (string) file_get_contents(__DIR__ . '/001_create_tables.sql');

        foreach ($this->splitStatements($sql) as $statement) {
            $this->pdo->exec($statement);
        }
    }

    public function rollback(): void
    {
        $tables = [
            'tb_Companies_Agents',
            'tb_Roles',
            'tb_Resource_Role_Access',
            'tb_Resource_Agent_Access',
            'tb_Module_Role_Permissions',
            'tb_Permissions',
            'tb_Module_Roles',
            'tb_Modules',
        ];

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /** @return array<string> */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current    = '';

        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $current .= $line . "\n";

            if (str_ends_with($trimmed, ';')) {
                $statements[] = trim($current);
                $current      = '';
            }
        }

        return array_filter($statements);
    }
}
