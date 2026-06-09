<?php

declare(strict_types=1);

use GustavoCabreira\Rbac\Rbac;
use GustavoCabreira\Rbac\Support\Resource;
use GustavoCabreira\Rbac\Support\RoleSlug;
use GustavoCabreira\Rbac\Tests\Support\TestDatabase;

const AM_COMPANY  = 1;
const AM_AGENT    = 200;
const AM_RES_ID   = 99;

function amResource(string $module = 'board', int $id = AM_RES_ID): Resource
{
    return new Resource($module, $id);
}

test('assignAgentRole upserts and does not duplicate on second call', function (): void {
    $r = amResource();
    Rbac::access()->assignAgentRole(AM_AGENT, $r, RoleSlug::VIEWER);
    Rbac::access()->assignAgentRole(AM_AGENT, $r, RoleSlug::ADMIN);

    $conn = TestDatabase::connection();
    $rows = $conn->select(
        'SELECT * FROM tb_Resource_Agent_Access
         WHERE raaResourceAgentAccessAgentID = ?
           AND raaResourceAgentAccessResourceID = ?',
        [AM_AGENT, AM_RES_ID]
    );

    expect($rows)->toHaveCount(1);

    $moduleRole = $conn->selectOne(
        'SELECT mroModuleRoleSlug FROM tb_Module_Roles WHERE mroModuleRoleID = ?',
        [$rows[0]['raaResourceAgentAccessModuleRoleID']]
    );

    expect($moduleRole['mroModuleRoleSlug'])->toBe(RoleSlug::ADMIN);
});

test('removeAgentRole deletes the access record', function (): void {
    $r = amResource();
    Rbac::access()->assignAgentRole(AM_AGENT, $r, RoleSlug::VIEWER);
    Rbac::access()->removeAgentRole(AM_AGENT, $r);

    $level = Rbac::resolver()->resolveLevel(AM_AGENT, AM_COMPANY, 'board', AM_RES_ID);

    expect($level)->toBe(0);
});

test('getEffectiveRole returns correct slug after assignment', function (): void {
    $r = amResource();
    Rbac::access()->assignAgentRole(AM_AGENT, $r, RoleSlug::ADMIN);

    $slug = Rbac::access()->getEffectiveRole(AM_AGENT, $r);

    expect($slug)->toBe(RoleSlug::ADMIN);
});

test('getEffectiveRole returns null with no access', function (): void {
    $slug = Rbac::access()->getEffectiveRole(AM_AGENT, amResource());

    expect($slug)->toBeNull();
});

test('assignRoleGroupRole upserts for role group', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription) VALUES (?, ?, ?)',
        [AM_COMPANY, 'Test Role', 'desc']
    );
    $roleId = (int) TestDatabase::pdo()->lastInsertId();

    $r = amResource();
    Rbac::access()->assignRoleGroupRole($roleId, $r, RoleSlug::VIEWER);
    Rbac::access()->assignRoleGroupRole($roleId, $r, RoleSlug::ADMIN);

    $rows = $conn->select(
        'SELECT * FROM tb_Resource_Role_Access
         WHERE rraResourceRoleAccessRoleID = ?
           AND rraResourceRoleAccessResourceID = ?',
        [$roleId, AM_RES_ID]
    );

    expect($rows)->toHaveCount(1);
});

test('removeRoleGroupRole deletes the access record', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription) VALUES (?, ?, ?)',
        [AM_COMPANY, 'Rm Role', 'desc']
    );
    $roleId = (int) TestDatabase::pdo()->lastInsertId();

    $r = amResource();
    Rbac::access()->assignRoleGroupRole($roleId, $r, RoleSlug::VIEWER);
    Rbac::access()->removeRoleGroupRole($roleId, $r);

    $rows = TestDatabase::connection()->select(
        'SELECT * FROM tb_Resource_Role_Access WHERE rraResourceRoleAccessRoleID = ?',
        [$roleId]
    );

    expect($rows)->toHaveCount(0);
});

test('agentsOnResource lists assigned agents', function (): void {
    $r = amResource();
    Rbac::access()->assignAgentRole(AM_AGENT, $r, RoleSlug::ADMIN);

    $agents = Rbac::access()->agentsOnResource($r);

    expect($agents)->toHaveCount(1)
        ->and($agents[0]['agent_id'])->toBe(AM_AGENT)
        ->and($agents[0]['role'])->toBe(RoleSlug::ADMIN);
});

test('roleGroupsOnResource lists assigned groups', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription) VALUES (?, ?, ?)',
        [AM_COMPANY, 'My Group', 'My desc']
    );
    $roleId = (int) TestDatabase::pdo()->lastInsertId();

    $r = amResource();
    Rbac::access()->assignRoleGroupRole($roleId, $r, RoleSlug::VIEWER);

    $groups = Rbac::access()->roleGroupsOnResource($r);

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['role_id'])->toBe($roleId)
        ->and($groups[0]['role'])->toBe(RoleSlug::VIEWER)
        ->and($groups[0]['name'])->toBe('My Group');
});

test('resourcesWithRole returns resource ids for a role group', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription) VALUES (?, ?, ?)',
        [AM_COMPANY, 'Res Role', 'desc']
    );
    $roleId = (int) TestDatabase::pdo()->lastInsertId();

    Rbac::access()->assignRoleGroupRole($roleId, amResource('board', 11), RoleSlug::VIEWER);
    Rbac::access()->assignRoleGroupRole($roleId, amResource('board', 12), RoleSlug::ADMIN);

    $ids = Rbac::access()->resourcesWithRole($roleId, 'board');

    expect($ids)->toContain(11)->and($ids)->toContain(12);
});
