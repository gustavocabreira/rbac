# Introduction

`gustavocabreira/rbac` is a **resource-scoped RBAC** package for PHP. It lets you assign roles to agents (users) or role groups on specific resource instances — not just globally.

## What is resource-scoped RBAC?

Traditional RBAC assigns a role to a user globally: _"Alice is an admin"_. Resource-scoped RBAC adds an instance dimension: _"Alice is an admin **on board #42**"_.

Each permission check takes three inputs:
- **Agent** — the user (identified by integer ID)
- **Module** — the resource type (e.g. `board`, `folder`)
- **Resource ID** — the specific instance (e.g. `42`)

## Direct access vs. via group

An agent can gain access in two ways:

| Path | Table |
|---|---|
| Direct | `tb_Resource_Agent_Access` |
| Via role group | `tb_Resource_Role_Access` + `tb_Companies_Agents` |

When both paths exist, the **highest level wins** (max of direct level and group level).

## Multi-tenant

Every access record belongs to a company. Set the active company once per request:

```php
Rbac::forCompany($companyIdFromRoute);
```

All subsequent calls to `Rbac::agent()`, `Rbac::role()`, and the resolvers automatically use that company.
