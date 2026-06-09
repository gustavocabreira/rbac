# Concedendo Acesso

## Via agente (ângulo do ator)

```php
use GustavoCabreira\Rbac\Rbac;
use GustavoCabreira\Rbac\Support\RoleSlug;

$agent = Rbac::agent(100);   // CompanyAgent, empresa do contexto
$role  = Rbac::role(5);      // HuggyRole (grupo de roles, tb_Roles)

// Atribuir usando um model que implementa Permissionable:
$agent->assign(RoleSlug::ADMIN, $board);

// Atribuir usando um grupo de roles:
$role->assign(RoleSlug::VIEWER, $board);

// Atribuir usando um Resource VO (quando você só tem IDs):
$agent->assign(RoleSlug::ADMIN, Rbac::resource('board', 42));

// Registrar quem concedeu (auditoria):
$agent->assign(RoleSlug::ADMIN, $board, grantedBy: 1);
```

## Via recurso (ângulo do recurso)

```php
// Requer a trait IsPermissionable no model

$board->grant($agent, RoleSlug::ADMIN);
$board->grant($role, RoleSlug::VIEWER);
$board->grant($agent, RoleSlug::OWNER, grantedBy: 1);
```

Ambos os ângulos produzem o mesmo registro no banco. Use o que for mais natural no contexto.
