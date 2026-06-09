# Interface Permissionable + Trait

## O contrato

Qualquer objeto que represente um recurso deve implementar `Contracts\Permissionable`:

```php
namespace GustavoCabreira\Rbac\Contracts;

interface Permissionable {
    public function rbacModuleSlug(): string;
    public function rbacResourceId(): int;
}
```

## Usando a trait (recomendado)

Adicione `IsPermissionable` ao seu model e implemente `rbacQueryByIds`:

```php
use GustavoCabreira\Rbac\Contracts\Permissionable;
use GustavoCabreira\Rbac\Concerns\IsPermissionable;

class Board extends Model implements Permissionable {
    use IsPermissionable;

    // Slug do módulo inferido pelo nome da classe ("board") — sobrescreva se necessário
    // Resource ID lido de $this->id — sobrescreva se a chave tiver outro nome

    protected static function rbacQueryByIds(array $ids): iterable {
        return static::whereIn('id', $ids)->get();
    }
}
```

## Sobrescrevendo convenções

| Método | Padrão | Sobrescreva quando |
|---|---|---|
| `rbacModuleSlug()` | nome curto da classe em minúsculas | nome da classe difere do slug do módulo |
| `rbacResourceId()` | `(int) $this->id` | chave primária não se chama `id` |

```php
// Sobrescrever o slug do módulo
public function rbacModuleSlug(): string {
    return 'kanban-board';
}

// Sobrescrever o resource ID
public function rbacResourceId(): int {
    return (int) $this->board_id;
}
```

## Resource VO

Quando você tem apenas IDs (sem objeto model), use `Support\Resource`:

```php
$resource = Rbac::resource('board', 42);
// ou
$resource = new Resource('board', 42);
// ou
$resource = Resource::from($somePermissionable);
```

`Resource` também implementa `Permissionable`, sendo intercambiável com objetos model em toda a API.
