# Registering Modules & Permissions

Before granting access to any resource, the module and its permissions must exist in the database. `DefinitionSeeder` is the tool for this — it is **idempotent** (safe to run multiple times) and uses `ON DUPLICATE KEY UPDATE` internally.

## Overview

The full seeding pipeline has four steps that must be run in order:

1. Register the **module** (`tb_Modules`)
2. Register the **module roles** with their levels (`tb_Module_Roles`)
3. Register the **permissions** for each module (`tb_Permissions`)
4. Map **which permissions each role grants** (`tb_Module_Role_Permissions`)

## Using `DefinitionSeeder`

```php
use GustavoCabreira\Rbac\Connection;
use GustavoCabreira\Rbac\Seeding\DefinitionSeeder;

$seeder = new DefinitionSeeder(new Connection($pdo));
$seeder->run(); // seeds board + folder modules with all roles and permissions
```

`run()` is the entry point. Call it during your application bootstrap, migration, or a dedicated artisan/CLI command — whichever fits your deploy pipeline.

## Adding a new module

To register a custom module (e.g. `pipeline`), insert it into `tb_Modules`:

```php
$conn = new Connection($pdo);

// 1. Register the module
$conn->statement(
    'INSERT INTO tb_Modules (modModuleSlug, modModuleName)
     VALUES (?, ?)
     ON DUPLICATE KEY UPDATE modModuleName = VALUES(modModuleName)',
    ['pipeline', 'Pipeline']
);
```

## Registering permissions for a module

Each permission slug follows the convention `{module}.{action}`:

```php
// 2. Look up the module ID just created
$module = $conn->selectOne(
    'SELECT modModuleID FROM tb_Modules WHERE modModuleSlug = ?',
    ['pipeline']
);

$moduleId = (int) $module['modModuleID'];

// 3. Insert permissions
$permissions = ['pipeline.view', 'pipeline.create', 'pipeline.edit', 'pipeline.delete'];

foreach ($permissions as $slug) {
    $name = ucfirst(str_replace('.', ' ', $slug)); // "Pipeline view", etc.

    $conn->statement(
        'INSERT INTO tb_Permissions (perPermissionModuleID, perPermissionName, perPermissionSlug)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE perPermissionName = VALUES(perPermissionName)',
        [$moduleId, $name, $slug]
    );
}
```

## Linking permissions to roles

After permissions exist, map them to the module roles (`owner`, `admin`, `viewer`):

```php
// 4. Define which permissions each role gets
$map = [
    'owner'  => ['pipeline.view', 'pipeline.create', 'pipeline.edit', 'pipeline.delete'],
    'admin'  => ['pipeline.view', 'pipeline.create', 'pipeline.edit'],
    'viewer' => ['pipeline.view'],
];

foreach ($map as $roleSlug => $permSlugs) {
    $role = $conn->selectOne(
        'SELECT mroModuleRoleID FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?',
        [$roleSlug]
    );

    foreach ($permSlugs as $permSlug) {
        $perm = $conn->selectOne(
            'SELECT perPermissionID FROM tb_Permissions WHERE perPermissionSlug = ?',
            [$permSlug]
        );

        $conn->statement(
            'INSERT INTO tb_Module_Role_Permissions
               (mrpModuleRolePermissionRoleID, mrpModuleRolePermissionPermissionID)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE
               mrpModuleRolePermissionRoleID = VALUES(mrpModuleRolePermissionRoleID)',
            [$role['mroModuleRoleID'], $perm['perPermissionID']]
        );
    }
}
```

## Encapsulating in a custom seeder

For maintainability, wrap the steps above in your own seeder class:

```php
class PipelineSeeder
{
    public function __construct(private Connection $conn) {}

    public function run(): void
    {
        $this->seedModule();
        $this->seedPermissions();
        $this->seedRoleMap();
    }

    // ... private methods following the pattern above
}
```

Then call it alongside `DefinitionSeeder::run()` in your bootstrap.

## Verifying the setup

After seeding, you can confirm the module is ready by checking that `can()` resolves correctly once an agent has been granted a role:

```php
Rbac::access()->assignAgentRole($agentId, Rbac::resource('pipeline', $pipelineId), RoleSlug::ADMIN);

$agent = Rbac::agent($agentId);
$agent->can('pipeline.edit', Rbac::resource('pipeline', $pipelineId)); // true
$agent->can('pipeline.delete', Rbac::resource('pipeline', $pipelineId)); // false — admin has no delete
```
