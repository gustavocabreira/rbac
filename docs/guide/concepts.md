# Concepts

## Modules

A **module** is a resource type, identified by a slug (e.g. `board`, `folder`). Modules are registered in `tb_Modules`.

## Module Roles (levels)

Each module role has a **level** (integer). Higher level = more permissive.

| Slug | Level |
|---|---|
| `owner` | 3 |
| `admin` | 2 |
| `viewer` | 1 |
| _(none)_ | 0 |

Use `Support\Level` and `Support\RoleSlug` constants instead of magic strings/numbers:

```php
use Huggy\Rbac\Support\Level;
use Huggy\Rbac\Support\RoleSlug;

Level::OWNER;   // 3
Level::ADMIN;   // 2
Level::VIEWER;  // 1
Level::NONE;    // 0

RoleSlug::OWNER;   // 'owner'
RoleSlug::ADMIN;   // 'admin'
RoleSlug::VIEWER;  // 'viewer'
```

## Permissions

Permissions are slugs in the form `{module}.{action}` (e.g. `board.edit`). They are assigned to module roles via `tb_Module_Role_Permissions`.

### Default permission map

| Role | Board | Folder |
|---|---|---|
| owner | view, create, edit, delete, share, archive | view, create, edit, delete, share |
| admin | view, create, edit, share, archive | view, create, edit, share |
| viewer | view | view |

## Role Groups

Role groups (`tb_Roles`) are company-scoped collections of agents. An agent belongs to a group via `tb_Companies_Agents`. When a group has access to a resource, all agents in that group inherit that access.

## Effective level

When checking permissions, the effective level is `max(direct_level, group_level)`. Level 0 means no access.
