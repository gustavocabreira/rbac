# Configuração

## Bootstrap (uma vez por processo)

Chame `Rbac::configure()` com uma instância de `PDO` na inicialização da aplicação — geralmente no `index.php`, service provider ou arquivo de bootstrap:

```php
use GustavoCabreira\Rbac\Rbac;

$pdo = new PDO('mysql:host=db;dbname=huggy', $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

Rbac::configure($pdo);
```

Isso configura os singletons `Connection`, `PermissionResolver` e `AccessManager`.

## Contexto de tenant (uma vez por request)

No seu middleware (ou equivalente), defina a empresa ativa para a request atual:

```php
Rbac::forCompany($companyIdFromRoute);
```

Todas as chamadas a `Rbac::agent()`, `Rbac::role()` e aos resolvers usarão automaticamente esse ID de empresa. Chamar `Rbac::company()` antes de `forCompany()` lança uma `RuntimeException`.

## Reset (testes)

Para isolamento em testes, resete o estado estático entre os testes:

```php
Rbac::reset();
```

Isso define todas as propriedades estáticas de volta para `null`, incluindo o contexto de empresa e as instâncias cacheadas de resolver/manager.
