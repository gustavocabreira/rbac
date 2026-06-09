# Listando Recursos Acessíveis

## accessibleIdsFor() — apenas IDs

```php
$ids = Board::accessibleIdsFor(100, Rbac::company());
// [1, 5, 9]
```

1 query SQL nas tabelas do RBAC. Retorna um array de IDs inteiros.

## accessibleFor() — instâncias do model

```php
$boards = Board::accessibleFor(100, Rbac::company());
// iterable de instâncias de Board
```

1 query para os IDs do RBAC, depois chama `rbacQueryByIds($ids)` que você implementa no model (1 query `WHERE IN`).

## accessibleIds() no CompanyAgent

```php
$agent = Rbac::agent(100);
$ids   = $agent->accessibleIds('board');  // equivalente a accessibleIdsFor mas a partir do objeto agent
```

## accessibleResources() agrupado por módulo

```php
$resources = Rbac::resolver()->accessibleResources(100, Rbac::company());
// ['board' => [1, 5, 9], 'folder' => [2, 7]]
```
