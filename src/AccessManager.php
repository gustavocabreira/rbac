<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac;

use GustavoCabreira\Rbac\Exceptions\ModuleNotFoundException;
use GustavoCabreira\Rbac\Exceptions\ModuleRoleNotFoundException;
use GustavoCabreira\Rbac\Support\Resource;

class AccessManager
{
    public function __construct(
        private Connection $connection,
        private PermissionResolver $resolver
    ) {}

    public function assignAgentRole(
        int $agentId,
        Resource $r,
        string $roleSlug,
        ?int $grantedBy = null
    ): void {
        $moduleId = $this->requireModuleId($r->rbacModuleSlug());
        $roleId   = $this->requireModuleRoleId($roleSlug);
        $companyId = $this->requireCompanyId();

        $sql = <<<SQL
INSERT INTO tb_Resource_Agent_Access
  (raaResourceAgentAccessCompanyID, raaResourceAgentAccessAgentID, raaResourceAgentAccessModuleID,
   raaResourceAgentAccessResourceID, raaResourceAgentAccessModuleRoleID, raaResourceAgentAccessGrantedBy)
VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  raaResourceAgentAccessModuleRoleID = VALUES(raaResourceAgentAccessModuleRoleID),
  raaResourceAgentAccessGrantedBy   = VALUES(raaResourceAgentAccessGrantedBy)
SQL;

        $this->connection->statement($sql, [
            $companyId,
            $agentId,
            $moduleId,
            $r->rbacResourceId(),
            $roleId,
            $grantedBy,
        ]);

        $this->resolver->flush();
    }

    public function removeAgentRole(int $agentId, Resource $r): void
    {
        $moduleId  = $this->requireModuleId($r->rbacModuleSlug());
        $companyId = $this->requireCompanyId();

        $this->connection->statement(
            'DELETE FROM tb_Resource_Agent_Access
             WHERE raaResourceAgentAccessAgentID = ?
               AND raaResourceAgentAccessModuleID = ?
               AND raaResourceAgentAccessResourceID = ?
               AND raaResourceAgentAccessCompanyID = ?',
            [$agentId, $moduleId, $r->rbacResourceId(), $companyId]
        );

        $this->resolver->flush();
    }

    public function assignRoleGroupRole(
        int $roleId,
        Resource $r,
        string $roleSlug,
        ?int $grantedBy = null
    ): void {
        $moduleId        = $this->requireModuleId($r->rbacModuleSlug());
        $moduleRoleId    = $this->requireModuleRoleId($roleSlug);
        $companyId       = $this->requireCompanyId();

        $sql = <<<SQL
INSERT INTO tb_Resource_Role_Access
  (rraResourceRoleAccessCompanyID, rraResourceRoleAccessRoleID, rraResourceRoleAccessModuleID,
   rraResourceRoleAccessResourceID, rraResourceRoleAccessModuleRoleID, rraResourceRoleAccessGrantedBy)
VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  rraResourceRoleAccessModuleRoleID = VALUES(rraResourceRoleAccessModuleRoleID),
  rraResourceRoleAccessGrantedBy   = VALUES(rraResourceRoleAccessGrantedBy)
SQL;

        $this->connection->statement($sql, [
            $companyId,
            $roleId,
            $moduleId,
            $r->rbacResourceId(),
            $moduleRoleId,
            $grantedBy,
        ]);

        $this->resolver->flush();
    }

    public function removeRoleGroupRole(int $roleId, Resource $r): void
    {
        $moduleId  = $this->requireModuleId($r->rbacModuleSlug());
        $companyId = $this->requireCompanyId();

        $this->connection->statement(
            'DELETE FROM tb_Resource_Role_Access
             WHERE rraResourceRoleAccessRoleID = ?
               AND rraResourceRoleAccessModuleID = ?
               AND rraResourceRoleAccessResourceID = ?
               AND rraResourceRoleAccessCompanyID = ?',
            [$roleId, $moduleId, $r->rbacResourceId(), $companyId]
        );

        $this->resolver->flush();
    }

    public function getEffectiveRole(int $agentId, Resource $r): ?string
    {
        $companyId = $this->requireCompanyId();
        $level     = $this->resolver->resolveLevel(
            $agentId,
            $companyId,
            $r->rbacModuleSlug(),
            $r->rbacResourceId()
        );

        if ($level === 0) {
            return null;
        }

        $row = $this->connection->selectOne(
            'SELECT mroModuleRoleSlug FROM tb_Module_Roles WHERE mroModuleRoleLevel = ?',
            [$level]
        );

        return $row ? $row['mroModuleRoleSlug'] : null;
    }

    /** @return array<array{agent_id:int,role:?string}> */
    public function agentsOnResource(Resource $r): array
    {
        $moduleId  = $this->requireModuleId($r->rbacModuleSlug());
        $companyId = $this->requireCompanyId();

        $rows = $this->connection->select(
            'SELECT raa.raaResourceAgentAccessAgentID AS agent_id,
                    mr.mroModuleRoleSlug AS role
             FROM tb_Resource_Agent_Access raa
             LEFT JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = raa.raaResourceAgentAccessModuleRoleID
             WHERE raa.raaResourceAgentAccessModuleID = ?
               AND raa.raaResourceAgentAccessResourceID = ?
               AND raa.raaResourceAgentAccessCompanyID = ?',
            [$moduleId, $r->rbacResourceId(), $companyId]
        );

        return array_map(function (array $row): array {
            return [
                'agent_id' => (int) $row['agent_id'],
                'role'     => $row['role'] ?? null,
            ];
        }, $rows);
    }

    /** @return array<array{role_id:int,role:?string,name:string,description:string}> */
    public function roleGroupsOnResource(Resource $r): array
    {
        $moduleId  = $this->requireModuleId($r->rbacModuleSlug());
        $companyId = $this->requireCompanyId();

        $rows = $this->connection->select(
            'SELECT rra.rraResourceRoleAccessRoleID AS role_id,
                    mr.mroModuleRoleSlug AS role,
                    ro.rolRoleName AS name,
                    ro.rolRoleDescription AS description
             FROM tb_Resource_Role_Access rra
             LEFT JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = rra.rraResourceRoleAccessModuleRoleID
             LEFT JOIN tb_Roles ro ON ro.rolRoleID = rra.rraResourceRoleAccessRoleID
             WHERE rra.rraResourceRoleAccessModuleID = ?
               AND rra.rraResourceRoleAccessResourceID = ?
               AND rra.rraResourceRoleAccessCompanyID = ?',
            [$moduleId, $r->rbacResourceId(), $companyId]
        );

        return array_map(function (array $row): array {
            return [
                'role_id'     => (int) $row['role_id'],
                'role'        => $row['role'] ?? null,
                'name'        => (string) $row['name'],
                'description' => (string) $row['description'],
            ];
        }, $rows);
    }

    /** @return array<array{role:string}> */
    public function rolesOn(int $agentId, Resource $r): array
    {
        $moduleId  = $this->requireModuleId($r->rbacModuleSlug());
        $companyId = $this->requireCompanyId();

        return $this->connection->select(
            'SELECT mr.mroModuleRoleSlug AS role
             FROM tb_Resource_Agent_Access raa
             JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = raa.raaResourceAgentAccessModuleRoleID
             WHERE raa.raaResourceAgentAccessAgentID = ?
               AND raa.raaResourceAgentAccessModuleID = ?
               AND raa.raaResourceAgentAccessResourceID = ?
               AND raa.raaResourceAgentAccessCompanyID = ?',
            [$agentId, $moduleId, $r->rbacResourceId(), $companyId]
        );
    }

    /** @return array<int> */
    public function resourcesWithRole(int $roleId, string $moduleSlug): array
    {
        $moduleId  = $this->requireModuleId($moduleSlug);
        $companyId = $this->requireCompanyId();

        $rows = $this->connection->select(
            'SELECT rraResourceRoleAccessResourceID AS resource_id
             FROM tb_Resource_Role_Access
             WHERE rraResourceRoleAccessRoleID = ?
               AND rraResourceRoleAccessModuleID = ?
               AND rraResourceRoleAccessCompanyID = ?',
            [$roleId, $moduleId, $companyId]
        );

        return array_map('intval', array_column($rows, 'resource_id'));
    }

    private function requireModuleId(string $moduleSlug): int
    {
        $row = $this->connection->selectOne(
            'SELECT modModuleID FROM tb_Modules WHERE modModuleSlug = ?',
            [$moduleSlug]
        );

        if ($row === null) {
            throw ModuleNotFoundException::forSlug($moduleSlug);
        }

        return (int) $row['modModuleID'];
    }

    private function requireModuleRoleId(string $roleSlug): int
    {
        $row = $this->connection->selectOne(
            'SELECT mroModuleRoleID FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?',
            [$roleSlug]
        );

        if ($row === null) {
            throw ModuleRoleNotFoundException::forSlug($roleSlug);
        }

        return (int) $row['mroModuleRoleID'];
    }

    private function requireCompanyId(): int
    {
        return Rbac::company();
    }
}
