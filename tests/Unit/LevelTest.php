<?php

declare(strict_types=1);

use GustavoCabreira\Rbac\Support\Level;
use GustavoCabreira\Rbac\Support\RoleSlug;

test('Level constants have correct integer values', function (): void {
    expect(Level::NONE)->toBe(0)
        ->and(Level::VIEWER)->toBe(1)
        ->and(Level::ADMIN)->toBe(2)
        ->and(Level::OWNER)->toBe(3);
});

test('RoleSlug constants have correct string values', function (): void {
    expect(RoleSlug::VIEWER)->toBe('viewer')
        ->and(RoleSlug::ADMIN)->toBe('admin')
        ->and(RoleSlug::OWNER)->toBe('owner');
});

test('owner level is greater than admin', function (): void {
    expect(Level::OWNER)->toBeGreaterThan(Level::ADMIN);
});

test('admin level is greater than viewer', function (): void {
    expect(Level::ADMIN)->toBeGreaterThan(Level::VIEWER);
});

test('viewer level is greater than none', function (): void {
    expect(Level::VIEWER)->toBeGreaterThan(Level::NONE);
});
