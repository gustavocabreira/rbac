# Granting Access

## Via the agent (actor angle)

```php
use Huggy\Rbac\Rbac;
use Huggy\Rbac\Support\RoleSlug;

$agent = Rbac::agent(100);   // CompanyAgent, company from context
$role  = Rbac::role(5);      // HuggyRole (role group, tb_Roles)

// Assign using a model that implements Permissionable:
$agent->assign(RoleSlug::ADMIN, $board);

// Assign using a role group:
$role->assign(RoleSlug::VIEWER, $board);

// Assign using a raw Resource VO (when you only have IDs):
$agent->assign(RoleSlug::ADMIN, Rbac::resource('board', 42));

// Record who granted (audit trail):
$agent->assign(RoleSlug::ADMIN, $board, grantedBy: 1);
```

## Via the resource (resource angle)

```php
// Requires the IsPermissionable trait on the model

$board->grant($agent, RoleSlug::ADMIN);
$board->grant($role, RoleSlug::VIEWER);
$board->grant($agent, RoleSlug::OWNER, grantedBy: 1);
```

Both angles produce the same database record. Use whichever reads more naturally in context.
