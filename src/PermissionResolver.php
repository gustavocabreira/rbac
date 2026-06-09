<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac;

class PermissionResolver
{
    /** @var array<string,int> */
    private array $levelCache = [];

    /** @var array<string,array<string>> */
    private static array $permissionCache = [];

    public function __construct(private Connection $connection) {}

    public function resolveLevel(
        int $agentId,
        int $companyId,
        string $moduleSlug,
        int $resourceId
    ): int {
        $key = "{$agentId}:{$companyId}:{$moduleSlug}:{$resourceId}";

        if (isset($this->levelCache[$key])) {
            return $this->levelCache[$key];
        }

        $sql = <<<SQL
SELECT COALESCE(MAX(q.level), 0) AS max_level
FROM (
    SELECT mr.mroModuleRoleLevel AS level
    FROM tb_Resource_Agent_Access raa
    JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = raa.raaResourceAgentAccessModuleRoleID
    JOIN tb_Modules m ON m.modModuleID = raa.raaResourceAgentAccessModuleID
    WHERE raa.raaResourceAgentAccessAgentID = ?
      AND raa.raaResourceAgentAccessCompanyID = ?
      AND m.modModuleSlug = ?
      AND raa.raaResourceAgentAccessResourceID = ?

    UNION ALL

    SELECT mr.mroModuleRoleLevel AS level
    FROM tb_Resource_Role_Access rra
    JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = rra.rraResourceRoleAccessModuleRoleID
    JOIN tb_Modules m ON m.modModuleID = rra.rraResourceRoleAccessModuleID
    JOIN tb_Companies_Agents ca
        ON ca.coaCompaniesAgentsCompanyID = rra.rraResourceRoleAccessCompanyID
        AND ca.coaCompaniesAgentsAgentID = ?
        AND ca.coaCompaniesAgentsAgentRole = rra.rraResourceRoleAccessRoleID
    WHERE rra.rraResourceRoleAccessCompanyID = ?
      AND m.modModuleSlug = ?
      AND rra.rraResourceRoleAccessResourceID = ?
) q
SQL;

        $row   = $this->connection->selectOne($sql, [
            $agentId, $companyId, $moduleSlug, $resourceId,
            $agentId, $companyId, $moduleSlug, $resourceId,
        ]);
        $level = (int) ($row['max_level'] ?? 0);

        $this->levelCache[$key] = $level;

        return $level;
    }

    /** @return array<string> */
    public function resolve(
        int $agentId,
        int $companyId,
        string $moduleSlug,
        int $resourceId
    ): array {
        $level = $this->resolveLevel($agentId, $companyId, $moduleSlug, $resourceId);

        if ($level === 0) {
            return [];
        }

        return $this->permissionsForLevel($level, $moduleSlug);
    }

    public function can(
        int $agentId,
        int $companyId,
        string $permission,
        int $resourceId
    ): bool {
        $parts      = explode('.', $permission);
        $moduleSlug = $parts[0];

        $permissions = $this->resolve($agentId, $companyId, $moduleSlug, $resourceId);

        $fullSlug = count($parts) === 1
            ? $permission
            : $permission;

        return in_array($fullSlug, $permissions, true);
    }

    /** @return array<string> */
    public function permissionsForLevel(int $level, string $moduleSlug): array
    {
        $key = "{$level}:{$moduleSlug}";

        if (isset(self::$permissionCache[$key])) {
            return self::$permissionCache[$key];
        }

        $sql = <<<SQL
SELECT p.perPermissionSlug
FROM tb_Module_Role_Permissions mrp
JOIN tb_Permissions p ON p.perPermissionID = mrp.mrpModuleRolePermissionPermissionID
JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = mrp.mrpModuleRolePermissionRoleID
JOIN tb_Modules m ON m.modModuleID = p.perPermissionModuleID
WHERE mr.mroModuleRoleLevel = ?
  AND m.modModuleSlug = ?
SQL;

        $rows   = $this->connection->select($sql, [$level, $moduleSlug]);
        $slugs  = array_column($rows, 'perPermissionSlug');

        self::$permissionCache[$key] = $slugs;

        return $slugs;
    }

    /** @return array<int> */
    public function accessibleResourceIds(
        int $agentId,
        int $companyId,
        string $moduleSlug
    ): array {
        $sql = <<<SQL
SELECT DISTINCT resource_id FROM (
    SELECT raa.raaResourceAgentAccessResourceID AS resource_id
    FROM tb_Resource_Agent_Access raa
    JOIN tb_Modules m ON m.modModuleID = raa.raaResourceAgentAccessModuleID
    WHERE raa.raaResourceAgentAccessAgentID = ?
      AND raa.raaResourceAgentAccessCompanyID = ?
      AND m.modModuleSlug = ?

    UNION

    SELECT rra.rraResourceRoleAccessResourceID AS resource_id
    FROM tb_Resource_Role_Access rra
    JOIN tb_Modules m ON m.modModuleID = rra.rraResourceRoleAccessModuleID
    JOIN tb_Companies_Agents ca
        ON ca.coaCompaniesAgentsCompanyID = rra.rraResourceRoleAccessCompanyID
        AND ca.coaCompaniesAgentsAgentID = ?
        AND ca.coaCompaniesAgentsAgentRole = rra.rraResourceRoleAccessRoleID
    WHERE rra.rraResourceRoleAccessCompanyID = ?
      AND m.modModuleSlug = ?
) t
SQL;

        $rows = $this->connection->select($sql, [
            $agentId, $companyId, $moduleSlug,
            $agentId, $companyId, $moduleSlug,
        ]);

        return array_map('intval', array_column($rows, 'resource_id'));
    }

    /** @return array<string,array<int>> */
    public function accessibleResources(int $agentId, int $companyId): array
    {
        $sql = <<<SQL
SELECT DISTINCT m.modModuleSlug AS module_slug, resource_id FROM (
    SELECT raa.raaResourceAgentAccessResourceID AS resource_id, raa.raaResourceAgentAccessModuleID AS module_id
    FROM tb_Resource_Agent_Access raa
    WHERE raa.raaResourceAgentAccessAgentID = ?
      AND raa.raaResourceAgentAccessCompanyID = ?

    UNION

    SELECT rra.rraResourceRoleAccessResourceID AS resource_id, rra.rraResourceRoleAccessModuleID AS module_id
    FROM tb_Resource_Role_Access rra
    JOIN tb_Companies_Agents ca
        ON ca.coaCompaniesAgentsCompanyID = rra.rraResourceRoleAccessCompanyID
        AND ca.coaCompaniesAgentsAgentID = ?
        AND ca.coaCompaniesAgentsAgentRole = rra.rraResourceRoleAccessRoleID
    WHERE rra.rraResourceRoleAccessCompanyID = ?
) t
JOIN tb_Modules m ON m.modModuleID = t.module_id
SQL;

        $rows   = $this->connection->select($sql, [$agentId, $companyId, $agentId, $companyId]);
        $result = [];

        foreach ($rows as $row) {
            $slug = $row['module_slug'];
            $result[$slug][] = (int) $row['resource_id'];
        }

        return $result;
    }

    public function flush(): void
    {
        $this->levelCache      = [];
        self::$permissionCache = [];
    }
}
