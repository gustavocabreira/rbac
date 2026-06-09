<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac;

use PDO;
use PDOStatement;

class Connection
{
    public function __construct(private PDO $pdo) {}

    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $stmt = $this->execute($sql, $bindings);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function select(string $sql, array $bindings = []): array
    {
        $stmt = $this->execute($sql, $bindings);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function statement(string $sql, array $bindings = []): void
    {
        $this->execute($sql, $bindings);
    }

    private function execute(string $sql, array $bindings): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt;
    }
}
