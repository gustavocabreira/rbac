# huggy/rbac

[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Resource-scoped RBAC for any PHP system. Framework-agnostic — only requires PDO + pdo_mysql.

## Quick Start

```bash
composer require huggy/rbac
```

```php
use Huggy\Rbac\Rbac;
use Huggy\Rbac\Support\RoleSlug;

// 1. Bootstrap (once, at application startup)
$pdo = new PDO('mysql:host=db;dbname=huggy', $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
Rbac::configure($pdo);

// 2. Set tenant context (once per request, in middleware)
Rbac::forCompany($companyIdFromRoute);

// 3. Grant
$agent = Rbac::agent(100);
$agent->assign(RoleSlug::ADMIN, $board);

// 4. Check
if ($agent->can('edit', $board)) {
    // allowed
}

// 5. List accessible resources (1 RBAC query + 1 batch query)
$ids    = Board::accessibleIdsFor(100, Rbac::company());
$boards = Board::accessibleFor(100, Rbac::company());
```

## Model integration

```php
use Huggy\Rbac\Contracts\Permissionable;
use Huggy\Rbac\Concerns\IsPermissionable;

class Board extends Model implements Permissionable {
    use IsPermissionable;
    // module slug = "board" (inferred from class name)
    // resource id = $this->id

    protected static function rbacQueryByIds(array $ids): iterable {
        return static::whereIn('id', $ids)->get();
    }
}
```

## Running tests

```bash
make build && make up && make install && make test
```

## Documentation

See the [full docs](https://gustavocabreira.github.io/rbac/) for API reference, concepts, and examples.

## License

MIT
