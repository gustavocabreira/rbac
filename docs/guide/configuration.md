# Configuration

## Bootstrap (once per process)

Call `Rbac::configure()` with a `PDO` instance at application startup — typically in your `index.php`, service provider, or bootstrap file:

```php
use Huggy\Rbac\Rbac;

$pdo = new PDO('mysql:host=db;dbname=huggy', $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

Rbac::configure($pdo);
```

This wires up the `Connection`, `PermissionResolver`, and `AccessManager` singletons.

## Tenant context (once per request)

In your middleware (or equivalent), set the active company for the current request:

```php
Rbac::forCompany($companyIdFromRoute);
```

Every call to `Rbac::agent()`, `Rbac::role()`, and the underlying resolvers will automatically use this company ID. Calling `Rbac::company()` before `forCompany()` throws a `RuntimeException`.

## Resetting (tests)

For test isolation, reset the static state between tests:

```php
Rbac::reset();
```

This sets all static properties back to `null`, including the company context and cached resolver/manager instances.
