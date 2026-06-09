# FAQ

## How do I map a module with a name different from the class?

Override `rbacModuleSlug()` in your model:

```php
class KanbanBoard extends Model implements Permissionable {
    use IsPermissionable;

    public function rbacModuleSlug(): string {
        return 'board';  // maps to the 'board' module in the DB
    }
}
```

## How do I audit who granted access?

Pass `grantedBy` to `assign()` or `grant()`:

```php
$agent->assign(RoleSlug::ADMIN, $board, grantedBy: $currentUserId);
$board->grant($agent, RoleSlug::ADMIN, grantedBy: $currentUserId);
```

The value is stored in `raaResourceAgentAccessGrantedBy` / `rraResourceRoleAccessGrantedBy`.

## Can I use a different primary key name?

Override `rbacResourceId()` in your model:

```php
public function rbacResourceId(): int {
    return (int) $this->board_id;
}
```

## Does this package create database tables?

**No.** In production, the schema is owned by your legacy application. The package only reads and writes existing tables. The `LocalMigrator` in `database/migrations-local/` is for the test environment only — never run it against production.

## How do I add a new module?

Use `DefinitionSeeder` or insert directly:

```php
$seeder = new DefinitionSeeder(new Connection($pdo));
// Or insert manually:
$conn->statement(
    'INSERT INTO tb_Modules (modModuleSlug, modModuleName) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE modModuleName = VALUES(modModuleName)',
    ['task', 'Task']
);
```

## Can an agent have different roles on different resources?

Yes — that is the whole point of resource-scoped RBAC. Each row in `tb_Resource_Agent_Access` is uniquely keyed on `(company, agent, module, resource)`, so an agent can be `admin` on board #1 and `viewer` on board #2 simultaneously.

## What happens if both direct and group access exist?

The effective level is `max(direct_level, group_level)`. The resolver uses a `UNION ALL + COALESCE(MAX(...))` query to compute this in a single round trip.
