# Conceitos

## Módulos

Um **módulo** é um tipo de recurso, identificado por um slug (ex.: `board`, `folder`). Módulos são registrados em `tb_Modules`.

## Module Roles (níveis)

Cada module role possui um **nível** (inteiro). Nível mais alto = mais permissivo.

| Slug | Nível |
|---|---|
| `owner` | 3 |
| `admin` | 2 |
| `viewer` | 1 |
| _(nenhum)_ | 0 |

Use as constantes `Support\Level` e `Support\RoleSlug` em vez de strings/números mágicos:

```php
use GustavoCabreira\Rbac\Support\Level;
use GustavoCabreira\Rbac\Support\RoleSlug;

Level::OWNER;   // 3
Level::ADMIN;   // 2
Level::VIEWER;  // 1
Level::NONE;    // 0

RoleSlug::OWNER;   // 'owner'
RoleSlug::ADMIN;   // 'admin'
RoleSlug::VIEWER;  // 'viewer'
```

## Permissões

Permissões são slugs no formato `{módulo}.{ação}` (ex.: `board.edit`). São atribuídas a module roles via `tb_Module_Role_Permissions`.

### Mapa de permissões padrão

| Role | Board | Folder |
|---|---|---|
| owner | view, create, edit, delete, share, archive | view, create, edit, delete, share |
| admin | view, create, edit, share, archive | view, create, edit, share |
| viewer | view | view |

## Grupos de Roles

Grupos de roles (`tb_Roles`) são coleções de agentes com escopo por empresa. Um agente pertence a um grupo via `tb_Companies_Agents`. Quando um grupo tem acesso a um recurso, todos os agentes desse grupo herdam esse acesso.

## Nível efetivo

Ao verificar permissões, o nível efetivo é `max(nível_direto, nível_grupo)`. Nível 0 significa sem acesso.
