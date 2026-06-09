# Revogando Acesso

## Via ator

```php
$agent->revoke($board);
$role->revoke($board);
```

## Via recurso

```php
$board->revoke($agent);
$board->revoke($role);
```

Revogar remove o registro de acesso do banco. Se o agente ainda tiver acesso via grupo, esse acesso de grupo não é afetado — apenas o registro direto (ou do grupo) sendo revogado é removido.
