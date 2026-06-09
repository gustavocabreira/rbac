# Revoking Access

## Via the actor

```php
$agent->revoke($board);
$role->revoke($board);
```

## Via the resource

```php
$board->revoke($agent);
$board->revoke($role);
```

Revoke removes the access record from the database. If the agent still has access via a group, that group access is unaffected — only the direct (or group) record being revoked is removed.
