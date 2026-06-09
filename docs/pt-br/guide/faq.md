# FAQ

## Como mapear um módulo com nome diferente da classe?

Sobrescreva `rbacModuleSlug()` no seu model:

```php
class KanbanBoard extends Model implements Permissionable {
    use IsPermissionable;

    public function rbacModuleSlug(): string {
        return 'board';  // mapeia para o módulo 'board' no banco
    }
}
```

## Como auditar quem concedeu acesso?

Passe `grantedBy` para `assign()` ou `grant()`:

```php
$agent->assign(RoleSlug::ADMIN, $board, grantedBy: $currentUserId);
$board->grant($agent, RoleSlug::ADMIN, grantedBy: $currentUserId);
```

O valor é armazenado em `raaResourceAgentAccessGrantedBy` / `rraResourceRoleAccessGrantedBy`.

## Posso usar um nome de chave primária diferente?

Sobrescreva `rbacResourceId()` no seu model:

```php
public function rbacResourceId(): int {
    return (int) $this->board_id;
}
```

## O pacote cria tabelas no banco?

**Não.** Em produção, o schema é de propriedade da sua aplicação legada. O pacote apenas lê e escreve nas tabelas existentes. O `LocalMigrator` em `database/migrations-local/` é apenas para o ambiente de teste — nunca rode em produção.

## Como adicionar um novo módulo?

Use `DefinitionSeeder` ou insira diretamente — veja a página [Registrando Módulos e Permissões](/pt-br/guide/seeding) para o guia completo.

## Um agente pode ter roles diferentes em recursos diferentes?

Sim — esse é o ponto central do RBAC com escopo por recurso. Cada linha em `tb_Resource_Agent_Access` é unicamente chaveada em `(empresa, agente, módulo, recurso)`, então um agente pode ser `admin` no board #1 e `viewer` no board #2 simultaneamente.

## O que acontece quando acesso direto e via grupo coexistem?

O nível efetivo é `max(nível_direto, nível_grupo)`. O resolver usa uma query `UNION ALL + COALESCE(MAX(...))` para calcular isso em uma única roundtrip.
