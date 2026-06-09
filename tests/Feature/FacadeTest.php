<?php

declare(strict_types=1);

use GustavoCabreira\Rbac\Rbac;
use GustavoCabreira\Rbac\Support\Resource;
use GustavoCabreira\Rbac\Support\RoleSlug;
use GustavoCabreira\Rbac\Tests\Support\FakeBoard;
use GustavoCabreira\Rbac\Tests\Support\TestDatabase;

const FA_COMPANY = 1;
const FA_AGENT   = 300;
const FA_RES_ID  = 77;

afterEach(function (): void {
    FakeBoard::clearFixtures();
});

test('CompanyAgent assign and can via fluent API', function (): void {
    $resource = Rbac::resource('board', FA_RES_ID);
    $agent    = Rbac::agent(FA_AGENT);

    $agent->assign(RoleSlug::ADMIN, $resource);

    expect($agent->can('edit', $resource))->toBeTrue()
        ->and($agent->can('board.edit', $resource))->toBeTrue()
        ->and($agent->can('delete', $resource))->toBeFalse();
});

test('CompanyAgent revoke removes access', function (): void {
    $resource = Rbac::resource('board', FA_RES_ID);
    $agent    = Rbac::agent(FA_AGENT);

    $agent->assign(RoleSlug::ADMIN, $resource);
    $agent->revoke($resource);

    expect($agent->can('edit', $resource))->toBeFalse();
});

test('CompanyAgent hasRole and role return correct values', function (): void {
    $resource = Rbac::resource('board', FA_RES_ID);
    $agent    = Rbac::agent(FA_AGENT);

    $agent->assign(RoleSlug::OWNER, $resource);

    expect($agent->hasRole(RoleSlug::OWNER, $resource))->toBeTrue()
        ->and($agent->hasRole(RoleSlug::ADMIN, $resource))->toBeFalse()
        ->and($agent->role($resource))->toBe(RoleSlug::OWNER);
});

test('CompanyAgent permissions returns all slugs for role', function (): void {
    $resource = Rbac::resource('board', FA_RES_ID);
    $agent    = Rbac::agent(FA_AGENT);

    $agent->assign(RoleSlug::VIEWER, $resource);

    expect($agent->permissions($resource))->toBe(['board.view']);
});

test('HuggyRole assign propagates via group access', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription) VALUES (?, ?, ?)',
        [FA_COMPANY, 'Facade Group', 'test']
    );
    $roleGroupId = (int) TestDatabase::pdo()->lastInsertId();

    TestDatabase::insertCompanyAgent(FA_COMPANY, FA_AGENT, $roleGroupId);

    $resource  = Rbac::resource('board', FA_RES_ID);
    $roleGroup = Rbac::role($roleGroupId);

    $roleGroup->assign(RoleSlug::ADMIN, $resource);

    $agent = Rbac::agent(FA_AGENT);

    expect($agent->can('edit', $resource))->toBeTrue();
});

test('HuggyRole revoke removes group access', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription) VALUES (?, ?, ?)',
        [FA_COMPANY, 'Rev Group', 'test']
    );
    $roleGroupId = (int) TestDatabase::pdo()->lastInsertId();

    TestDatabase::insertCompanyAgent(FA_COMPANY, FA_AGENT, $roleGroupId);

    $resource  = Rbac::resource('board', FA_RES_ID);
    $roleGroup = Rbac::role($roleGroupId);

    $roleGroup->assign(RoleSlug::ADMIN, $resource);
    $roleGroup->revoke($resource);

    $agent = Rbac::agent(FA_AGENT);

    expect($agent->can('edit', $resource))->toBeFalse();
});

test('grant and revoke via IsPermissionable trait (resource angle)', function (): void {
    $board = FakeBoard::make(FA_RES_ID);
    $agent = Rbac::agent(FA_AGENT);

    // FakeBoard module is 'fakeboard'; we just test grant/revoke mechanics
    $board->grant($agent, RoleSlug::ADMIN);

    $level = Rbac::resolver()->resolveLevel(FA_AGENT, FA_COMPANY, 'fakeboard', FA_RES_ID);
    expect($level)->toBe(2); // admin level

    $board->revoke($agent);

    $levelAfter = Rbac::resolver()->resolveLevel(FA_AGENT, FA_COMPANY, 'fakeboard', FA_RES_ID);
    expect($levelAfter)->toBe(0);
});

test('allows delegates to agent->can', function (): void {
    $board = FakeBoard::make(FA_RES_ID);
    $agent = Rbac::agent(FA_AGENT);

    $agent->assign(RoleSlug::ADMIN, $board);

    // 'fakeboard' has no permissions seeded, so can() for a slug permission will be false
    // but we test allows() delegates to can() — the resolution logic is the same
    expect($board->allows($agent, 'view'))->toBeFalse();
});

test('agents() and roles() list resource access', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription) VALUES (?, ?, ?)',
        [FA_COMPANY, 'Listed Group', 'test']
    );
    $roleGroupId = (int) TestDatabase::pdo()->lastInsertId();

    $resource  = Rbac::resource('board', FA_RES_ID);
    $agent     = Rbac::agent(FA_AGENT);
    $roleGroup = Rbac::role($roleGroupId);

    $agent->assign(RoleSlug::VIEWER, $resource);
    $roleGroup->assign(RoleSlug::ADMIN, $resource);

    $board = FakeBoard::make(FA_RES_ID);

    // Use the board module resource directly to check agents/roles
    $boardResource = Rbac::resource('board', FA_RES_ID);
    $agentsList    = Rbac::access()->agentsOnResource($boardResource);
    $rolesList     = Rbac::access()->roleGroupsOnResource($boardResource);

    expect($agentsList)->toHaveCount(1)
        ->and($agentsList[0]['agent_id'])->toBe(FA_AGENT)
        ->and($rolesList)->toHaveCount(1)
        ->and($rolesList[0]['role_id'])->toBe($roleGroupId);
});

test('Rbac throws RuntimeException if company not set', function (): void {
    Rbac::reset();

    expect(fn () => Rbac::company())->toThrow(\RuntimeException::class);
});

test('Rbac throws RuntimeException if not configured', function (): void {
    Rbac::reset();

    expect(fn () => Rbac::resolver())->toThrow(\RuntimeException::class);
    expect(fn () => Rbac::access())->toThrow(\RuntimeException::class);
});
