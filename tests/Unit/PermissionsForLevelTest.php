<?php

declare(strict_types=1);

use GustavoCabreira\Rbac\Connection;
use GustavoCabreira\Rbac\PermissionResolver;
use GustavoCabreira\Rbac\Support\Level;
use Mockery\MockInterface;

beforeEach(function (): void {
    // Clear static permission cache that persists across test instances
    (new \GustavoCabreira\Rbac\PermissionResolver(Mockery::mock(\GustavoCabreira\Rbac\Connection::class)))->flush();
});

afterEach(function (): void {
    Mockery::close();
});

test('permissionsForLevel queries correct columns', function (): void {
    /** @var Connection&MockInterface $conn */
    $conn = Mockery::mock(Connection::class);

    $conn->shouldReceive('select')
        ->once()
        ->withArgs(function (string $sql, array $bindings): bool {
            return str_contains($sql, 'perPermissionSlug')
                && str_contains($sql, 'mroModuleRoleLevel')
                && $bindings === [Level::ADMIN, 'board'];
        })
        ->andReturn([
            ['perPermissionSlug' => 'board.view'],
            ['perPermissionSlug' => 'board.edit'],
        ]);

    $resolver = new PermissionResolver($conn);
    $result   = $resolver->permissionsForLevel(Level::ADMIN, 'board');

    expect($result)->toBe(['board.view', 'board.edit']);
});

test('permissionsForLevel caches result statically', function (): void {
    /** @var Connection&MockInterface $conn */
    $conn = Mockery::mock(Connection::class);

    $conn->shouldReceive('select')
        ->once()
        ->andReturn([['perPermissionSlug' => 'board.view']]);

    $resolver = new PermissionResolver($conn);
    $resolver->permissionsForLevel(Level::VIEWER, 'board');
    $resolver->permissionsForLevel(Level::VIEWER, 'board');
});

test('flush clears static permission cache', function (): void {
    /** @var Connection&MockInterface $conn */
    $conn = Mockery::mock(Connection::class);

    $conn->shouldReceive('select')
        ->twice()
        ->andReturn([['perPermissionSlug' => 'board.view']]);

    $resolver = new PermissionResolver($conn);
    $resolver->permissionsForLevel(Level::VIEWER, 'board');
    $resolver->flush();
    $resolver->permissionsForLevel(Level::VIEWER, 'board');
});
