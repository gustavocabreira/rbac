# Listing Accessible Resources

## accessibleIdsFor() — just IDs

```php
$ids = Board::accessibleIdsFor(100, Rbac::company());
// [1, 5, 9]
```

1 SQL query against the RBAC tables. Returns an array of integer IDs.

## accessibleFor() — model instances

```php
$boards = Board::accessibleFor(100, Rbac::company());
// iterable of Board instances
```

1 query for RBAC IDs, then calls `rbacQueryByIds($ids)` which you implement in the model (1 `WHERE IN` query).

## accessibleIds() on CompanyAgent

```php
$agent = Rbac::agent(100);
$ids   = $agent->accessibleIds('board');  // same as accessibleIdsFor but from agent object
```

## accessibleResources() grouped by module

```php
$resources = Rbac::resolver()->accessibleResources(100, Rbac::company());
// ['board' => [1, 5, 9], 'folder' => [2, 7]]
```
