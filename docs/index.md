---
layout: home

hero:
  name: gustavocabreira/rbac
  text: Resource-scoped RBAC
  tagline: Framework-agnostic PHP RBAC with per-resource permissions, direct agent access, and role group inheritance.
  image:
    src: /logo.svg
    alt: gustavocabreira/rbac
  actions:
    - theme: brand
      text: Get Started
      link: /guide/introduction
    - theme: alt
      text: API Reference
      link: /guide/api-reference

features:
  - title: Framework-Agnostic
    icon: 🔌
    details: Works with Laravel, Symfony, Slim, or plain PHP. Only requires PDO + pdo_mysql — no Eloquent, no containers.
  - title: Resource-Scoped
    icon: 🎯
    details: Permissions are scoped to module + resource ID. An agent can be admin on board #1 but viewer on board #2.
  - title: Multi-Tenant
    icon: 🏢
    details: Built-in company context. Set the tenant once per request with Rbac::forCompany() and the whole API respects it.
  - title: Direct + Group Access
    icon: 👥
    details: Agents can have direct access or inherit it from a role group. The effective level is always the maximum of both.
---

## Quick Start

```bash
composer require gustavocabreira/rbac
```

```php
use Huggy\Rbac\Rbac;
use Huggy\Rbac\Support\RoleSlug;

// Bootstrap (once)
Rbac::configure(new PDO('mysql:host=db;dbname=app', $user, $pass));

// Per-request (middleware)
Rbac::forCompany($companyId);

// Grant
$agent = Rbac::agent(100);
$agent->assign(RoleSlug::ADMIN, $board);

// Check
if ($agent->can('edit', $board)) {
    // ...
}

// List
$ids = Board::accessibleIdsFor(100, Rbac::company());
```
