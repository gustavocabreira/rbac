<?php

declare(strict_types=1);

use GustavoCabreira\Rbac\Rbac;
use GustavoCabreira\Rbac\Support\RoleSlug;
use GustavoCabreira\Rbac\Tests\Support\FakeBoard;
use GustavoCabreira\Rbac\Tests\Support\TestDatabase;

const AR_COMPANY = 1;
const AR_AGENT   = 400;

afterEach(function (): void {
    FakeBoard::clearFixtures();
});

test('accessibleIdsFor returns ids via direct and group access', function (): void {
    $conn = TestDatabase::connection();
    $conn->statement(
        'INSERT INTO tb_Roles (rolRoleCompanyID, rolRoleName, rolRoleDescription) VALUES (?, ?, ?)',
        [AR_COMPANY, 'Access Group', 'test']
    );
    $roleGroupId = (int) TestDatabase::pdo()->lastInsertId();
    TestDatabase::insertCompanyAgent(AR_COMPANY, AR_AGENT, $roleGroupId);

    $agent = Rbac::agent(AR_AGENT);
    $group = Rbac::role($roleGroupId);

    $directBoard = FakeBoard::make(50);
    $groupBoard  = FakeBoard::make(60);

    $agent->assign(RoleSlug::VIEWER, $directBoard);
    $group->assign(RoleSlug::VIEWER, $groupBoard);

    $ids = FakeBoard::accessibleIdsFor(AR_AGENT, AR_COMPANY);

    expect($ids)->toContain(50)->and($ids)->toContain(60);
});

test('accessibleFor returns model instances for accessible ids', function (): void {
    $agent = Rbac::agent(AR_AGENT);

    $b1 = FakeBoard::make(70);
    $b2 = FakeBoard::make(71);

    $agent->assign(RoleSlug::VIEWER, $b1);
    $agent->assign(RoleSlug::VIEWER, $b2);

    $boards = FakeBoard::accessibleFor(AR_AGENT, AR_COMPANY);
    $ids    = [];
    foreach ($boards as $b) {
        $ids[] = $b->id;
    }

    expect($ids)->toContain(70)->and($ids)->toContain(71);
});

test('accessibleResources groups by module slug', function (): void {
    $agent = Rbac::agent(AR_AGENT);

    $b = FakeBoard::make(80);
    $agent->assign(RoleSlug::VIEWER, $b);

    $resources = Rbac::resolver()->accessibleResources(AR_AGENT, AR_COMPANY);

    expect($resources)->toHaveKey('fakeboard')
        ->and($resources['fakeboard'])->toContain(80);
});

test('FakeBoard module slug is inferred from class name', function (): void {
    $board = new FakeBoard(1);

    expect($board->rbacModuleSlug())->toBe('fakeboard');
});

test('CompanyAgent accessibleIds matches resolver', function (): void {
    $agent = Rbac::agent(AR_AGENT);
    $b1    = FakeBoard::make(90);
    $b2    = FakeBoard::make(91);

    $agent->assign(RoleSlug::VIEWER, $b1);
    $agent->assign(RoleSlug::VIEWER, $b2);

    $agentIds   = $agent->accessibleIds('fakeboard');
    $resolverIds = Rbac::resolver()->accessibleResourceIds(AR_AGENT, AR_COMPANY, 'fakeboard');

    sort($agentIds);
    sort($resolverIds);

    expect($agentIds)->toBe($resolverIds);
});
