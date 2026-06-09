<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac\Seeding;

use GustavoCabreira\Rbac\Connection;

class DefinitionSeeder
{
    public function __construct(private Connection $connection) {}

    public function run(): void
    {
        $this->seedModules();
        $this->seedModuleRoles();
        $this->seedPermissions();
        $this->seedRolePermissionMap();
    }

    private function seedModules(): void
    {
        $modules = [
            ['board',  'Board'],
            ['folder', 'Folder'],
        ];

        foreach ($modules as [$slug, $name]) {
            $this->connection->statement(
                'INSERT INTO tb_Modules (modModuleSlug, modModuleName)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE modModuleName = VALUES(modModuleName)',
                [$slug, $name]
            );
        }
    }

    private function seedModuleRoles(): void
    {
        $roles = [
            ['owner',  'Owner',  3],
            ['admin',  'Admin',  2],
            ['viewer', 'Viewer', 1],
        ];

        foreach ($roles as [$slug, $name, $level]) {
            $this->connection->statement(
                'INSERT INTO tb_Module_Roles (mroModuleRoleSlug, mroModuleRoleName, mroModuleRoleLevel)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   mroModuleRoleName  = VALUES(mroModuleRoleName),
                   mroModuleRoleLevel = VALUES(mroModuleRoleLevel)',
                [$slug, $name, $level]
            );
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'board'  => ['board.view', 'board.create', 'board.edit', 'board.delete', 'board.share', 'board.archive'],
            'folder' => ['folder.view', 'folder.create', 'folder.edit', 'folder.delete', 'folder.share'],
        ];

        foreach ($permissions as $moduleSlug => $slugs) {
            $moduleRow = $this->connection->selectOne(
                'SELECT modModuleID FROM tb_Modules WHERE modModuleSlug = ?',
                [$moduleSlug]
            );

            if ($moduleRow === null) {
                continue;
            }

            $moduleId = (int) $moduleRow['modModuleID'];

            foreach ($slugs as $slug) {
                $name = ucfirst(str_replace('.', ' ', $slug));
                $this->connection->statement(
                    'INSERT INTO tb_Permissions (perPermissionModuleID, perPermissionName, perPermissionSlug)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                       perPermissionName = VALUES(perPermissionName)',
                    [$moduleId, $name, $slug]
                );
            }
        }
    }

    private function seedRolePermissionMap(): void
    {
        $map = [
            'owner' => [
                'board'  => ['board.view', 'board.create', 'board.edit', 'board.delete', 'board.share', 'board.archive'],
                'folder' => ['folder.view', 'folder.create', 'folder.edit', 'folder.delete', 'folder.share'],
            ],
            'admin' => [
                'board'  => ['board.view', 'board.create', 'board.edit', 'board.share', 'board.archive'],
                'folder' => ['folder.view', 'folder.create', 'folder.edit', 'folder.share'],
            ],
            'viewer' => [
                'board'  => ['board.view'],
                'folder' => ['folder.view'],
            ],
        ];

        foreach ($map as $roleSlug => $modulePerms) {
            $roleRow = $this->connection->selectOne(
                'SELECT mroModuleRoleID FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?',
                [$roleSlug]
            );

            if ($roleRow === null) {
                continue;
            }

            $roleId = (int) $roleRow['mroModuleRoleID'];

            foreach ($modulePerms as $permSlugs) {
                foreach ($permSlugs as $permSlug) {
                    $permRow = $this->connection->selectOne(
                        'SELECT perPermissionID FROM tb_Permissions WHERE perPermissionSlug = ?',
                        [$permSlug]
                    );

                    if ($permRow === null) {
                        continue;
                    }

                    $permId = (int) $permRow['perPermissionID'];

                    $this->connection->statement(
                        'INSERT INTO tb_Module_Role_Permissions
                           (mrpModuleRolePermissionRoleID, mrpModuleRolePermissionPermissionID)
                         VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE
                           mrpModuleRolePermissionRoleID = VALUES(mrpModuleRolePermissionRoleID)',
                        [$roleId, $permId]
                    );
                }
            }
        }
    }
}
