# Permissionable Interface + Trait

## The contract

Any object representing a resource must implement `Contracts\Permissionable`:

```php
namespace Huggy\Rbac\Contracts;

interface Permissionable {
    public function rbacModuleSlug(): string;
    public function rbacResourceId(): int;
}
```

## Using the trait (recommended)

Add `IsPermissionable` to your model and implement `rbacQueryByIds`:

```php
use Huggy\Rbac\Contracts\Permissionable;
use Huggy\Rbac\Concerns\IsPermissionable;

class Board extends Model implements Permissionable {
    use IsPermissionable;

    // Module slug inferred from class name ("board") — override if needed
    // Resource ID read from $this->id — override if needed

    protected static function rbacQueryByIds(array $ids): iterable {
        return static::whereIn('id', $ids)->get();
    }
}
```

## Convention overrides

| Method | Default | Override when |
|---|---|---|
| `rbacModuleSlug()` | lowercase class short name | class name differs from module slug |
| `rbacResourceId()` | `(int) $this->id` | primary key is not named `id` |

```php
// Override module slug
public function rbacModuleSlug(): string {
    return 'kanban-board';
}

// Override resource ID
public function rbacResourceId(): int {
    return (int) $this->board_id;
}
```

## Resource VO

When you only have IDs (no model object), use `Support\Resource`:

```php
$resource = Rbac::resource('board', 42);
// or
$resource = new Resource('board', 42);
// or
$resource = Resource::from($somePermissionable);
```

`Resource` also implements `Permissionable`, so it's interchangeable with model objects everywhere in the API.
