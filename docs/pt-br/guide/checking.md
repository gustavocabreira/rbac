# Verificando Permissões

Todas as verificações consideram **ambos os caminhos** — acesso direto do agente e acesso herdado por grupo — em uma única query SQL.

## can()

```php
// Forma curta: prefixa o módulo automaticamente
if ($agent->can('edit', $board)) {
    // resolve 'board.edit'
}

// Forma com slug completo:
$agent->can('board.delete', $board);
```

## hasRole() e role()

```php
$agent->hasRole(RoleSlug::ADMIN, $board);  // bool
$agent->role($board);                      // 'admin' | 'viewer' | 'owner' | null
```

## permissions()

```php
$agent->permissions($board);
// ['board.view', 'board.edit', 'board.create', 'board.share', 'board.archive']
```

## allows() (ângulo do recurso)

```php
$board->allows($agent, 'edit');   // bool — equivalente a $agent->can('edit', $board)
```
