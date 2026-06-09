---
layout: home

hero:
  name: gustavocabreira/rbac
  text: RBAC com escopo por recurso
  tagline: RBAC PHP agnóstico de framework com permissões por instância de recurso, acesso direto por agente e herança via grupo de roles.
  image:
    src: /logo.svg
    alt: gustavocabreira/rbac
  actions:
    - theme: brand
      text: Começar
      link: /pt-br/guide/introduction
    - theme: alt
      text: Referência da API
      link: /pt-br/guide/api-reference

features:
  - title: Agnóstico de Framework
    icon: 🔌
    details: Funciona com Laravel, Symfony, Slim ou PHP puro. Exige apenas PDO + pdo_mysql — sem Eloquent, sem containers.
  - title: Escopo por Recurso
    icon: 🎯
    details: Permissões com escopo de módulo + ID de recurso. Um agente pode ser admin no board #1 e viewer no board #2.
  - title: Multi-Tenant
    icon: 🏢
    details: Contexto de empresa embutido. Defina o tenant uma vez por request com Rbac::forCompany() e toda a API respeita isso.
  - title: Acesso Direto + por Grupo
    icon: 👥
    details: Agentes podem ter acesso direto ou herdá-lo de um grupo de roles. O nível efetivo é sempre o máximo dos dois.
---

## Quick Start

```bash
composer require gustavocabreira/rbac
```

```php
use GustavoCabreira\Rbac\Rbac;
use GustavoCabreira\Rbac\Support\RoleSlug;

// Bootstrap (uma vez)
Rbac::configure(new PDO('mysql:host=db;dbname=app', $user, $pass));

// Por request (middleware)
Rbac::forCompany($companyId);

// Conceder
$agent = Rbac::agent(100);
$agent->assign(RoleSlug::ADMIN, $board);

// Verificar
if ($agent->can('edit', $board)) {
    // ...
}

// Listar
$ids = Board::accessibleIdsFor(100, Rbac::company());
```
