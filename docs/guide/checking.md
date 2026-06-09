# Checking Permissions

All checks consider **both paths** — direct agent access and group-inherited access — in a single SQL query.

## can()

```php
// Short form: prefixes the module automatically
if ($agent->can('edit', $board)) {
    // resolves 'board.edit'
}

// Full slug form:
$agent->can('board.delete', $board);
```

## hasRole() and role()

```php
$agent->hasRole(RoleSlug::ADMIN, $board);  // bool
$agent->role($board);                      // 'admin' | 'viewer' | 'owner' | null
```

## permissions()

```php
$agent->permissions($board);
// ['board.view', 'board.edit', 'board.create', 'board.share', 'board.archive']
```

## allows() (resource angle)

```php
$board->allows($agent, 'edit');   // bool — same as $agent->can('edit', $board)
```
