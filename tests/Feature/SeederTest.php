<?php

declare(strict_types=1);

use GustavoCabreira\Rbac\Seeding\DefinitionSeeder;
use GustavoCabreira\Rbac\Tests\Support\TestDatabase;

test('DefinitionSeeder creates modules', function (): void {
    $conn  = TestDatabase::connection();
    $rows  = $conn->select('SELECT modModuleSlug FROM tb_Modules ORDER BY modModuleSlug', []);
    $slugs = array_column($rows, 'modModuleSlug');

    expect($slugs)->toContain('board')->and($slugs)->toContain('folder');
});

test('DefinitionSeeder creates module roles with correct levels', function (): void {
    $conn = TestDatabase::connection();

    $owner  = $conn->selectOne('SELECT mroModuleRoleLevel FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?', ['owner']);
    $admin  = $conn->selectOne('SELECT mroModuleRoleLevel FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?', ['admin']);
    $viewer = $conn->selectOne('SELECT mroModuleRoleLevel FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?', ['viewer']);

    expect((int) $owner['mroModuleRoleLevel'])->toBe(3)
        ->and((int) $admin['mroModuleRoleLevel'])->toBe(2)
        ->and((int) $viewer['mroModuleRoleLevel'])->toBe(1);
});

test('DefinitionSeeder creates permissions for board', function (): void {
    $conn  = TestDatabase::connection();
    $rows  = $conn->select(
        'SELECT perPermissionSlug FROM tb_Permissions WHERE perPermissionSlug LIKE ?',
        ['board.%']
    );
    $slugs = array_column($rows, 'perPermissionSlug');

    expect($slugs)->toContain('board.view')
        ->and($slugs)->toContain('board.create')
        ->and($slugs)->toContain('board.edit')
        ->and($slugs)->toContain('board.delete')
        ->and($slugs)->toContain('board.share')
        ->and($slugs)->toContain('board.archive');
});

test('DefinitionSeeder creates permissions for folder without archive', function (): void {
    $conn  = TestDatabase::connection();
    $rows  = $conn->select(
        'SELECT perPermissionSlug FROM tb_Permissions WHERE perPermissionSlug LIKE ?',
        ['folder.%']
    );
    $slugs = array_column($rows, 'perPermissionSlug');

    expect($slugs)->toContain('folder.view')
        ->and($slugs)->not->toContain('folder.archive');
});

test('DefinitionSeeder is idempotent (running twice does not duplicate)', function (): void {
    $seeder = new DefinitionSeeder(TestDatabase::connection());
    $seeder->run(); // second run — should not add rows

    $conn = TestDatabase::connection();

    // board + folder (from seeder) + fakeboard (seeded by TestCase::setUp)
    $modules = $conn->select('SELECT COUNT(*) AS cnt FROM tb_Modules', []);
    expect((int) $modules[0]['cnt'])->toBe(3);

    $roles = $conn->select('SELECT COUNT(*) AS cnt FROM tb_Module_Roles', []);
    expect((int) $roles[0]['cnt'])->toBe(3);
});

test('admin role map has correct board permissions', function (): void {
    $conn = TestDatabase::connection();

    $rows = $conn->select(
        'SELECT p.perPermissionSlug
         FROM tb_Module_Role_Permissions mrp
         JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = mrp.mrpModuleRolePermissionRoleID
         JOIN tb_Permissions p ON p.perPermissionID = mrp.mrpModuleRolePermissionPermissionID
         WHERE mr.mroModuleRoleSlug = ?
           AND p.perPermissionSlug LIKE ?',
        ['admin', 'board.%']
    );
    $slugs = array_column($rows, 'perPermissionSlug');

    expect($slugs)->toContain('board.view')
        ->and($slugs)->toContain('board.edit')
        ->and($slugs)->not->toContain('board.delete');
});
