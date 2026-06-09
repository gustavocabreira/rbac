<?php

declare(strict_types=1);

use GustavoCabreira\Rbac\Rbac;
use GustavoCabreira\Rbac\Support\Level;
use GustavoCabreira\Rbac\Support\RoleSlug;
use GustavoCabreira\Rbac\Tests\Support\TestDatabase;

const COMPANY_ID  = 1;
const AGENT_ID    = 100;
const RESOURCE_ID = 42;
const ROLE_ID     = 5;

// Helper to assign agent access directly in DB (bypassing AccessManager company context dependency)
function seedAgentAccess(int $agentId, int $companyId, string $moduleSlug, int $resourceId, string $roleSlug): void
{
    $conn = TestDatabase::connection();

    $module = $conn->selectOne('SELECT modModuleID FROM tb_Modules WHERE modModuleSlug = ?', [$moduleSlug]);
    $role   = $conn->selectOne('SELECT mroModuleRoleID FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?', [$roleSlug]);

    $conn->statement(
        'INSERT INTO tb_Resource_Agent_Access
           (raaResourceAgentAccessCompanyID, raaResourceAgentAccessAgentID, raaResourceAgentAccessModuleID,
            raaResourceAgentAccessResourceID, raaResourceAgentAccessModuleRoleID)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           raaResourceAgentAccessModuleRoleID = VALUES(raaResourceAgentAccessModuleRoleID)',
        [$companyId, $agentId, $module['modModuleID'], $resourceId, $role['mroModuleRoleID']]
    );
}

function seedRoleAccess(int $roleGroupId, int $companyId, string $moduleSlug, int $resourceId, string $roleSlug): void
{
    $conn = TestDatabase::connection();

    $module = $conn->selectOne('SELECT modModuleID FROM tb_Modules WHERE modModuleSlug = ?', [$moduleSlug]);
    $role   = $conn->selectOne('SELECT mroModuleRoleID FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?', [$roleSlug]);

    $conn->statement(
        'INSERT INTO tb_Resource_Role_Access
           (rraResourceRoleAccessCompanyID, rraResourceRoleAccessRoleID, rraResourceRoleAccessModuleID,
            rraResourceRoleAccessResourceID, rraResourceRoleAccessModuleRoleID)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           rraResourceRoleAccessModuleRoleID = VALUES(rraResourceRoleAccessModuleRoleID)',
        [$companyId, $roleGroupId, $module['modModuleID'], $resourceId, $role['mroModuleRoleID']]
    );
}

test('agent with no access returns level 0 and empty permissions', function (): void {
    $resolver = Rbac::resolver();

    expect($resolver->resolveLevel(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID))->toBe(0)
        ->and($resolver->resolve(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID))->toBe([])
        ->and($resolver->can(AGENT_ID, COMPANY_ID, 'board.view', RESOURCE_ID))->toBeFalse();
});

test('viewer gets only board.view', function (): void {
    seedAgentAccess(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID, RoleSlug::VIEWER);
    Rbac::resolver()->flush();

    $resolver = Rbac::resolver();
    $perms    = $resolver->resolve(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID);

    expect($perms)->toBe(['board.view'])
        ->and($resolver->can(AGENT_ID, COMPANY_ID, 'board.view', RESOURCE_ID))->toBeTrue()
        ->and($resolver->can(AGENT_ID, COMPANY_ID, 'board.edit', RESOURCE_ID))->toBeFalse();
});

test('admin gets multiple board permissions but not folder.delete', function (): void {
    seedAgentAccess(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID, RoleSlug::ADMIN);
    seedAgentAccess(AGENT_ID, COMPANY_ID, 'folder', RESOURCE_ID, RoleSlug::ADMIN);
    Rbac::resolver()->flush();

    $resolver    = Rbac::resolver();
    $boardPerms  = $resolver->resolve(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID);
    $folderPerms = $resolver->resolve(AGENT_ID, COMPANY_ID, 'folder', RESOURCE_ID);

    expect($boardPerms)->toContain('board.view')
        ->and($boardPerms)->toContain('board.edit')
        ->and($boardPerms)->toContain('board.create')
        ->and($folderPerms)->not->toContain('folder.delete');
});

test('owner gets all permissions', function (): void {
    seedAgentAccess(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID, RoleSlug::OWNER);
    Rbac::resolver()->flush();

    $perms = Rbac::resolver()->resolve(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID);

    expect($perms)->toContain('board.view')
        ->and($perms)->toContain('board.create')
        ->and($perms)->toContain('board.edit')
        ->and($perms)->toContain('board.delete')
        ->and($perms)->toContain('board.share')
        ->and($perms)->toContain('board.archive');
});

test('merge: direct viewer + group admin yields admin level (max wins)', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription)
         VALUES (?, ?, ?)',
        [COMPANY_ID, 'Test Group', 'Test']
    );
    $roleGroupId = (int) TestDatabase::pdo()->lastInsertId();

    seedAgentAccess(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID, RoleSlug::VIEWER);
    seedRoleAccess($roleGroupId, COMPANY_ID, 'board', RESOURCE_ID, RoleSlug::ADMIN);
    TestDatabase::insertCompanyAgent(COMPANY_ID, AGENT_ID, $roleGroupId);
    Rbac::resolver()->flush();

    $level = Rbac::resolver()->resolveLevel(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID);

    expect($level)->toBe(Level::ADMIN);
});

test('resolveLevel uses SQL verbatim with 8 bindings', function (): void {
    $level = Rbac::resolver()->resolveLevel(AGENT_ID, COMPANY_ID, 'board', RESOURCE_ID);

    expect($level)->toBe(0);
});

test('folder module permissions differ from board', function (): void {
    seedAgentAccess(AGENT_ID, COMPANY_ID, 'folder', RESOURCE_ID, RoleSlug::ADMIN);
    Rbac::resolver()->flush();

    $perms = Rbac::resolver()->resolve(AGENT_ID, COMPANY_ID, 'folder', RESOURCE_ID);

    expect($perms)->toContain('folder.view')
        ->and($perms)->toContain('folder.edit')
        ->and($perms)->not->toContain('folder.delete');
});

test('accessibleResourceIds returns ids from both direct and group access', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription)
         VALUES (?, ?, ?)',
        [COMPANY_ID, 'Group A', 'Test']
    );
    $roleGroupId = (int) TestDatabase::pdo()->lastInsertId();

    seedAgentAccess(AGENT_ID, COMPANY_ID, 'board', 10, RoleSlug::VIEWER);
    seedRoleAccess($roleGroupId, COMPANY_ID, 'board', 20, RoleSlug::ADMIN);
    TestDatabase::insertCompanyAgent(COMPANY_ID, AGENT_ID, $roleGroupId);
    Rbac::resolver()->flush();

    $ids = Rbac::resolver()->accessibleResourceIds(AGENT_ID, COMPANY_ID, 'board');

    expect($ids)->toContain(10)->and($ids)->toContain(20);
});
