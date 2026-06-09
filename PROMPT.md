# PROMPT — Construir o pacote PHP `gustavocabreira/rbac` (RBAC resource-scoped, agnóstico, MySQL)

## Objetivo

Construir um pacote PHP **autônomo e agnóstico de framework** que implementa um sistema de
**RBAC escopado por recurso** (permissões por módulo + instância de recurso), com acesso
**direto por agente** e **via grupo de roles**. O pacote deve poder ser instalado via Composer
em **qualquer sistema PHP** (Laravel, Symfony, Slim, PHP puro) sem depender de nenhum framework.

Este pacote é a extração de um RBAC que hoje vive acoplado a um app Laravel. A lógica de negócio,
o schema e o comportamento devem ser **reproduzidos fielmente** — o SQL do resolver é fornecido
**verbatim** abaixo e deve ser mantido.

## Requisitos não-funcionais (obrigatórios)

1. **PHP mínimo: 8.0** (`"php": ">=8.0"` no composer.json). Restrições de linguagem:
   - **Proibido:** enums nativos, `readonly`, tipo de retorno `never`, `array_is_list()`,
     sintaxe de first-class callable (`$fn(...)`), `new` em initializers.
   - **Permitido:** promoção de propriedades no construtor, `match`, nullsafe `?->`,
     argumentos nomeados, `str_contains`/`str_starts_with`.
   - Substituir o enum `ModuleRole` por **constantes de classe** / value objects.
   - `declare(strict_types=1);` em **todos** os arquivos.
2. **Agnóstico de framework:** núcleo em PHP puro. **Sem** Eloquent, **sem** Illuminate,
   **sem** Blade, **sem** container de serviço. Nenhuma dependência de runtime além de `ext-pdo` + `pdo_mysql`.
3. **Banco: MySQL** (o banco **já existe** em produção). O pacote recebe uma instância de `PDO`
   (MySQL) por injeção. **Em produção o schema é garantido pelas migrations do legado — o pacote
   NÃO cria nem altera tabelas em produção.**
4. **Migrations locais (só dev/teste):** o pacote inclui migrations próprias, executadas
   **apenas localmente**, que recriam as 8 tabelas num MySQL descartável para a suite de testes
   rodar isolada. **Nunca** rodar essas migrations em produção.
5. **Ambiente Docker com PHP-FPM** (PHP 8.0) + serviço MySQL de teste.
6. **Testes com PestPHP** usando **dados mockados** (fixtures controladas semeadas no MySQL de
   teste; nenhuma conexão a banco de produção). Cobertura de cenários espelhando a suite original.
7. **Documentação:** site de docs bonito (VitePress) com deploy automático no GitHub Pages.

## Schema (8 tabelas — nomes e colunas ORIGINAIS, não renomear)

> Estas tabelas **já existem** no MySQL de produção (criadas pelo legado). O pacote apenas as
> consulta/escreve. Os nomes mixed-case são intencionais e devem ser preservados exatamente — o
> pacote precisa casar com o que o legado criou.
> **Atenção MySQL:** o casamento de nomes de tabela depende de `lower_case_table_names`; o pacote
> deve usar os nomes **exatamente** como no legado. A tabela `tb_Companies_Agents` é **crucial** —
> é o mapeamento agente→grupo usado no JOIN do caminho de acesso via-grupo.
>
> As **migrations locais** (`database/migrations-local/`) recriam estas mesmas tabelas em MySQL
> para os testes — DDL MySQL (`ENGINE=InnoDB`, `AUTO_INCREMENT`, índices/uniques abaixo).

- **`tb_Modules`** — `modModuleID` (PK AI), `modModuleSlug` (unique), `modModuleName`
- **`tb_Module_Roles`** — `mroModuleRoleID` (PK AI), `mroModuleRoleSlug`, `mroModuleRoleName`,
  `mroModuleRoleLevel` (int — hierarquia: maior = mais permissivo)
- **`tb_Permissions`** — `perPermissionID` (PK AI), `perPermissionModuleID` (FK→Modules),
  `perPermissionName`, `perPermissionSlug` (ex.: `board.edit`)
- **`tb_Module_Role_Permissions`** — `mrpModuleRolePermissionID` (PK AI),
  `mrpModuleRolePermissionRoleID` (FK→Module_Roles), `mrpModuleRolePermissionPermissionID` (FK→Permissions)
- **`tb_Resource_Agent_Access`** — `raaResourceAgentAccessID` (PK AI),
  `raaResourceAgentAccessCompanyID`, `raaResourceAgentAccessAgentID`,
  `raaResourceAgentAccessModuleID`, `raaResourceAgentAccessResourceID`,
  `raaResourceAgentAccessModuleRoleID`, `raaResourceAgentAccessGrantedBy` (nullable).
  **Unique:** (CompanyID, AgentID, ModuleID, ResourceID). Índices: (AgentID, ModuleID, ResourceID), (CompanyID).
- **`tb_Resource_Role_Access`** — `rraResourceRoleAccessID` (PK AI),
  `rraResourceRoleAccessCompanyID`, `rraResourceRoleAccessRoleID`,
  `rraResourceRoleAccessModuleID`, `rraResourceRoleAccessResourceID`,
  `rraResourceRoleAccessModuleRoleID`, `rraResourceRoleAccessGrantedBy` (nullable).
  **Unique:** (CompanyID, RoleID, ModuleID, ResourceID). Índices: (RoleID, ModuleID, ResourceID), (CompanyID).
- **`tb_Roles`** — `rolRoleID` (PK AI), `rolRoleCompanyID`, `rolRoleName`, `rolRoleDescription`,
  `rolRoleStatus`, `rolRoleCreatedBy`, `rolRoleUpdatedBy`, `rolRoleDeletedBy`
- **`tb_Companies_Agents`** — `coaCompaniesAgentsCompanyID`, `coaCompaniesAgentsAgentID`,
  `coaCompaniesAgentsAgentRole` (= id do grupo de role do agente naquela company). Índice em
  (CompanyID, AgentID, AgentRole).

> As **uniques** acima são necessárias para o upsert (`ON DUPLICATE KEY UPDATE`) funcionar. Como
> em prod o legado é dono do schema, garantir que essas uniques existam lá; as migrations locais
> as recriam para os testes.

## Conceito de níveis (substitui o enum)

`Support\Level` com constantes inteiras e `Support\RoleSlug` com strings:

```php
final class Level {
    public const OWNER  = 3;
    public const ADMIN  = 2;
    public const VIEWER = 1;
    public const NONE   = 0;
}
final class RoleSlug {
    public const OWNER  = 'owner';
    public const ADMIN  = 'admin';
    public const VIEWER = 'viewer';
}
```

Resolução por **nível**: pega o **maior nível** entre acesso direto e via-grupo (`max` vence).
Nível 0 = sem acesso.

## API pública

Namespace raiz: `Huggy\Rbac`.

### `Connection` (wrapper fino sobre PDO MySQL)

Recebe `PDO` no construtor. Expõe `selectOne(string $sql, array $bindings): ?array`,
`select(string $sql, array $bindings): array` e `statement(string $sql, array $bindings): void`.
Substitui o `DB::connection('panel')` do código original.

### `Rbac` (facade/registry estático + contexto de tenant)

Bootstrap do app + contexto de tenant por request. É o ponto de entrada de DX.

```php
final class Rbac {
    private static ?Connection $connection = null;
    private static ?PermissionResolver $resolver = null;
    private static ?AccessManager $access = null;
    private static ?int $companyId = null;

    /** Bootstrap do app (uma vez). */
    public static function configure(PDO $pdo): void {
        self::$connection = new Connection($pdo);
        self::$resolver   = new PermissionResolver(self::$connection);
        self::$access     = new AccessManager(self::$connection, self::$resolver);
    }

    /** Middleware (uma vez por request): define o tenant ativo. */
    public static function forCompany(int $companyId): void { self::$companyId = $companyId; }
    public static function company(): int { /* lança RuntimeException se não setado */ }

    /** Factories de DX (company vem do contexto). */
    public static function agent(int $id): CompanyAgent { return new CompanyAgent($id, self::company()); }
    public static function role(int $id): HuggyRole     { return new HuggyRole($id, self::company()); }
    public static function resource(string $module, int $id): Resource { return new Resource($module, $id); }

    /** Engine de baixo nível (trait/casos avançados). */
    public static function resolver(): PermissionResolver { /* lança se não configurado */ }
    public static function access(): AccessManager { /* idem */ }

    /** Isolamento de testes. */
    public static function reset(): void { self::$connection = self::$resolver = self::$access = self::$companyId = null; }
}
```

`Rbac::resolver()`/`access()`/`company()` lançam `RuntimeException` se chamados antes de
`configure()`/`forCompany()`.

### `PermissionResolver` (leitura — porta 1:1 do original)

Recebe `Connection`. Caches: `$levelCache` por instância (chave
`"{agentId}:{companyId}:{moduleSlug}:{resourceId}"`) e `self::$permissionCache` estático
(chave `"{level}:{moduleSlug}"`).

Métodos:
- `resolveLevel(int $agentId, int $companyId, string $moduleSlug, int $resourceId): int`
- `resolve(int $agentId, int $companyId, string $moduleSlug, int $resourceId): array` → list de slugs
- `can(int $agentId, int $companyId, string $permission, int $resourceId): bool`
  (extrai o módulo de `explode('.', $permission)[0]`)
- `permissionsForLevel(int $level, string $moduleSlug): array`
- `accessibleResourceIds(int $agentId, int $companyId, string $moduleSlug): array` → list de IDs
- `accessibleResources(int $agentId, int $companyId): array` → `['board' => [1,5,9], 'folder' => [2,7]]`
  (mesma query do `accessibleResourceIds` **sem** o filtro de módulo, com `m.modModuleSlug` no
  SELECT, agrupado em PHP — opcional)
- `flush(): void`

> **Importante:** `resolveLevel`/`can` já consideram **os dois caminhos** — o acesso direto do
> agente (`tb_Resource_Agent_Access`) **e** o acesso herdado do grupo de role dele
> (`tb_Resource_Role_Access` via `tb_Companies_Agents`) — pegando o **maior nível**. Ou seja,
> "o agente OU o role dele tem a permissão" é resolvido numa única query.

**SQL VERBATIM (manter exatamente; compatível com MySQL):**

`resolveLevel`:
```sql
SELECT COALESCE(MAX(q.level), 0) AS max_level
FROM (
    SELECT mr.mroModuleRoleLevel AS level
    FROM tb_Resource_Agent_Access raa
    JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = raa.raaResourceAgentAccessModuleRoleID
    JOIN tb_Modules m ON m.modModuleID = raa.raaResourceAgentAccessModuleID
    WHERE raa.raaResourceAgentAccessAgentID = ?
      AND raa.raaResourceAgentAccessCompanyID = ?
      AND m.modModuleSlug = ?
      AND raa.raaResourceAgentAccessResourceID = ?

    UNION ALL

    SELECT mr.mroModuleRoleLevel AS level
    FROM tb_Resource_Role_Access rra
    JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = rra.rraResourceRoleAccessModuleRoleID
    JOIN tb_Modules m ON m.modModuleID = rra.rraResourceRoleAccessModuleID
    JOIN tb_Companies_Agents ca
        ON ca.coaCompaniesAgentsCompanyID = rra.rraResourceRoleAccessCompanyID
        AND ca.coaCompaniesAgentsAgentID = ?
        AND ca.coaCompaniesAgentsAgentRole = rra.rraResourceRoleAccessRoleID
    WHERE rra.rraResourceRoleAccessCompanyID = ?
      AND m.modModuleSlug = ?
      AND rra.rraResourceRoleAccessResourceID = ?
) q
```
Bindings: `[agentId, companyId, moduleSlug, resourceId, agentId, companyId, moduleSlug, resourceId]`.

`permissionsForLevel`:
```sql
SELECT p.perPermissionSlug
FROM tb_Module_Role_Permissions mrp
JOIN tb_Permissions p ON p.perPermissionID = mrp.mrpModuleRolePermissionPermissionID
JOIN tb_Module_Roles mr ON mr.mroModuleRoleID = mrp.mrpModuleRolePermissionRoleID
JOIN tb_Modules m ON m.modModuleID = p.perPermissionModuleID
WHERE mr.mroModuleRoleLevel = ?
  AND m.modModuleSlug = ?
```

`accessibleResourceIds` (filtrado por módulo — `UNION` com DISTINCT):
```sql
SELECT DISTINCT resource_id FROM (
    SELECT raa.raaResourceAgentAccessResourceID AS resource_id
    FROM tb_Resource_Agent_Access raa
    JOIN tb_Modules m ON m.modModuleID = raa.raaResourceAgentAccessModuleID
    WHERE raa.raaResourceAgentAccessAgentID = ?
      AND raa.raaResourceAgentAccessCompanyID = ?
      AND m.modModuleSlug = ?

    UNION

    SELECT rra.rraResourceRoleAccessResourceID AS resource_id
    FROM tb_Resource_Role_Access rra
    JOIN tb_Modules m ON m.modModuleID = rra.rraResourceRoleAccessModuleID
    JOIN tb_Companies_Agents ca
        ON ca.coaCompaniesAgentsCompanyID = rra.rraResourceRoleAccessCompanyID
        AND ca.coaCompaniesAgentsAgentID = ?
        AND ca.coaCompaniesAgentsAgentRole = rra.rraResourceRoleAccessRoleID
    WHERE rra.rraResourceRoleAccessCompanyID = ?
      AND m.modModuleSlug = ?
) t
```
Bindings: `[agentId, companyId, moduleSlug, agentId, companyId, moduleSlug]`.

### `AccessManager` (engine de escrita/consulta — id-based)

Recebe `Connection` + `PermissionResolver`. Toda escrita chama `resolver->flush()` ao final.
É o motor por baixo da fachada fluente; aceita IDs escalares ou `Support\Resource`.

- `assignAgentRole(int $agentId, Resource $r, string $roleSlug, ?int $grantedBy = null): void`
  — upsert em `tb_Resource_Agent_Access`.
- `removeAgentRole(int $agentId, Resource $r): void`
- `assignRoleGroupRole(int $roleId, Resource $r, string $roleSlug, ?int $grantedBy = null): void`
  — upsert em `tb_Resource_Role_Access`.
- `removeRoleGroupRole(int $roleId, Resource $r): void`
- `getEffectiveRole(int $agentId, Resource $r): ?string` — slug do nível resolvido (maior vence);
  `SELECT mroModuleRoleSlug FROM tb_Module_Roles WHERE mroModuleRoleLevel = ?`.
- `agentsOnResource(Resource $r): array` → `[['agent_id' => int, 'role' => ?string], ...]`
- `roleGroupsOnResource(Resource $r): array` → `[['role_id','role','name','description'], ...]`
- `rolesOn(int $agentId, Resource $r): array` — roles diretos do agente
- `resourcesWithRole(int $roleId, string $moduleSlug): array`

**Upsert (MySQL):** `INSERT ... ON DUPLICATE KEY UPDATE <colunas>=VALUES(<colunas>)`, apoiado nas
constraints **unique**. Ex. (agente):
```sql
INSERT INTO tb_Resource_Agent_Access
  (raaResourceAgentAccessCompanyID, raaResourceAgentAccessAgentID, raaResourceAgentAccessModuleID,
   raaResourceAgentAccessResourceID, raaResourceAgentAccessModuleRoleID, raaResourceAgentAccessGrantedBy)
VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  raaResourceAgentAccessModuleRoleID = VALUES(raaResourceAgentAccessModuleRoleID),
  raaResourceAgentAccessGrantedBy   = VALUES(raaResourceAgentAccessGrantedBy)
```

### Fachada fluente (DX) — `CompanyAgent` e `HuggyRole`

Wrappers finos que carregam `(id + companyId)` e delegam ao engine. São o caminho recomendado.

`CompanyAgent`:
- `assign(string $rbacRole, Permissionable $resource, ?int $grantedBy = null): void`
- `revoke(Permissionable $resource): void`
- `can(string $permission, Permissionable $resource): bool` — `can('edit', $board)` prefixa o
  módulo automaticamente (`board.edit`); aceita slug completo (`board.edit`) também.
- `hasRole(string $rbacRole, Permissionable $resource): bool`
- `role(Permissionable $resource): ?string` — role efetivo
- `permissions(Permissionable $resource): array`
- `accessibleIds(string $moduleSlug): array`

`HuggyRole` (grupo de roles, `tb_Roles`):
- `assign(string $rbacRole, Permissionable $resource, ?int $grantedBy = null): void`
- `revoke(Permissionable $resource): void`
- `hasRole(string $rbacRole, Permissionable $resource): bool`
- `resourcesWith(string $moduleSlug): array`

### Identificação do recurso — interface `Permissionable` + trait

Toda a fachada tipa o recurso como `Contracts\Permissionable`:

```php
namespace Huggy\Rbac\Contracts;

interface Permissionable {
    public function rbacModuleSlug(): string;
    public function rbacResourceId(): int;
}
```

> **Por que interface + trait (e não `extends`):** PHP tem herança simples; um model do host já
> faz `extends Model`. Por isso o contrato é uma **interface** e o comportamento padrão vem de uma
> **trait**. O VO `Support\Resource` também implementa `Permissionable`, então model e VO são
> intercambiáveis em toda a API.

Trait com convenção (`Concerns\IsPermissionable`):

```php
namespace Huggy\Rbac\Concerns;

trait IsPermissionable {
    /** Convenção: módulo = nome curto da classe em minúsculas. Sobrescreva se divergir. */
    public function rbacModuleSlug(): string {
        return strtolower((new \ReflectionClass($this))->getShortName());
    }

    /** Convenção: id = propriedade `id`. Sobrescreva se o id estiver em outra propriedade. */
    public function rbacResourceId(): int {
        return (int) $this->id;
    }

    // --- ângulo pelo recurso ---
    public function grant(object $grantee, string $rbacRole, ?int $grantedBy = null): void { /* delega a CompanyAgent|HuggyRole */ }
    public function revoke(object $grantee): void { /* idem */ }
    public function allows(CompanyAgent $agent, string $permission): bool { return $agent->can($permission, $this); }
    public function agents(): array { return Rbac::access()->agentsOnResource(Resource::from($this)); }
    public function roles(): array  { return Rbac::access()->roleGroupsOnResource(Resource::from($this)); }

    // --- listagem de recursos acessíveis ---
    /** @param list<int> $ids @return iterable — host implementa em 1 linha. */
    abstract protected static function rbacQueryByIds(array $ids): iterable;

    public static function accessibleFor(int $agentId, int $companyId): iterable {
        return static::rbacQueryByIds(static::accessibleIdsFor($agentId, $companyId));
    }
    public static function accessibleIdsFor(int $agentId, int $companyId): array {
        return Rbac::resolver()->accessibleResourceIds($agentId, $companyId, (new static())->rbacModuleSlug());
    }
}
```

`Support\Resource`:
```php
final class Resource implements Permissionable {
    public function __construct(private string $moduleSlug, private int $resourceId) {}
    public function rbacModuleSlug(): string { return $this->moduleSlug; }
    public function rbacResourceId(): int { return $this->resourceId; }
    public static function from(Permissionable $p): self { return new self($p->rbacModuleSlug(), $p->rbacResourceId()); }
}
```

Garantias de listagem: **1 query no RBAC** (IDs) + **1 query no host** (em lote, `whereIn`) —
nunca por recurso.

### `Seeding\DefinitionSeeder` (definições — porta do `ModulePermissionSeeder`)

API fluente/array para registrar **módulos**, **module roles** (slug + level), **permissões**
(slug por módulo) e o **mapa role→permissões**. Idempotente (upsert por slug). Em produção essas
definições podem já vir do legado; o seeder é usado nos **testes** e fica disponível como utilitário.

Preset atual:
- Módulos: `board`, `folder`.
- Module roles: `owner` (3), `admin` (2), `viewer` (1).
- Permissões board: `board.view/create/edit/delete/share/archive`; folder: `folder.view/create/edit/delete/share`.
- Mapa: **owner** = todas; **admin** = view, create, edit, archive, share (board) / sem delete (folder);
  **viewer** = só view.

### Migrations locais (`database/migrations-local/`) — SÓ DEV/TESTE

- DDL MySQL (`*.sql`) + um `LocalMigrator` (PHP puro, recebe `PDO`) com `migrate(): void` (cria as
  8 tabelas com PK AI, uniques e índices) e `rollback(): void` (drop).
- **Nunca** invocar em produção. Documentar no topo dos arquivos: "LOCAL/TEST ONLY — produção usa
  o schema do legado". A suite Pest chama `LocalMigrator::migrate()` no setup do banco de teste.

### Exceptions

`Exceptions\ModuleNotFoundException`, `Exceptions\ModuleRoleNotFoundException` (equivalentes ao
`firstOrFail`). `Rbac::resolver()`/`access()`/`company()` lançam `RuntimeException` se chamados
antes de `configure()`/`forCompany()`.

## Exemplos de uso

```php
use Huggy\Rbac\Rbac;
use Huggy\Rbac\Support\RoleSlug;

// 1) Bootstrap do app (uma vez)
$pdo = new PDO('mysql:host=db;dbname=huggy', $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
Rbac::configure($pdo);

// 2) Middleware (uma vez por request) — define o tenant da request inteira
Rbac::forCompany($companyIdDaRota);
```

```php
// 3) Model do host — uma interface + uma trait, zero config no caso comum
use Huggy\Rbac\Contracts\Permissionable;
use Huggy\Rbac\Concerns\IsPermissionable;

class Board extends Model implements Permissionable {
    use IsPermissionable;
    // módulo inferido = "board"; id = $this->id (ambos sobrescrevíveis)

    protected static function rbacQueryByIds(array $ids): iterable {
        return static::whereIn('id', $ids)->get(); // usado por accessibleFor()
    }
}
```

### Conceder permissão

```php
$agent = Rbac::agent(100);   // CompanyAgent (company vem do contexto)
$role  = Rbac::role(5);      // HuggyRole (grupo, tb_Roles)

// pelo ator, passando o model direto (infere 'board'):
$agent->assign(RoleSlug::ADMIN, $board);

// pelo ator, com o grupo de role:
$role->assign(RoleSlug::VIEWER, $board);

// quando só se tem o id (sem o objeto):
$agent->assign(RoleSlug::ADMIN, Rbac::resource('board', 42));

// registrando quem concedeu (auditoria):
$agent->assign(RoleSlug::ADMIN, $board, grantedBy: 1);

// ângulo pelo recurso:
$board->grant($agent, RoleSlug::ADMIN);
$board->grant($role, RoleSlug::VIEWER);
```

### Revogar permissão

```php
// pelo ator:
$agent->revoke($board);
$role->revoke($board);

// pelo recurso:
$board->revoke($agent);
$board->revoke($role);
```

### Checar permissão / role

```php
// checagem por permissão — considera acesso DIRETO do agente OU via grupo de role dele:
if ($agent->can('edit', $board)) {        // resolve 'board.edit'
    // ...
}
$agent->can('board.delete', $board);       // slug completo também funciona

// checagem por role efetivo:
$agent->hasRole(RoleSlug::ADMIN, $board);  // bool
$agent->role($board);                      // 'admin' | 'viewer' | 'owner' | null
$agent->permissions($board);               // ['board.view','board.edit', ...]

// pelo recurso:
$board->allows($agent, 'edit');            // bool

// listar recursos que o agente acessa (1 query RBAC + 1 query em lote):
$boards = Board::accessibleFor(100, Rbac::company());  // models
$ids    = Board::accessibleIdsFor(100, Rbac::company()); // [1, 5, 9]
```

## Estrutura de diretórios

```
huggy-rbac/
├── composer.json
├── docker-compose.yml
├── docker/php/Dockerfile
├── Makefile
├── src/
│   ├── Rbac.php
│   ├── Connection.php
│   ├── PermissionResolver.php
│   ├── AccessManager.php
│   ├── CompanyAgent.php
│   ├── HuggyRole.php
│   ├── Support/{Level.php, RoleSlug.php, Resource.php}
│   ├── Contracts/Permissionable.php
│   ├── Concerns/IsPermissionable.php
│   ├── Seeding/DefinitionSeeder.php
│   └── Exceptions/{ModuleNotFoundException.php, ModuleRoleNotFoundException.php}
├── database/migrations-local/        # LOCAL/TEST ONLY
│   ├── LocalMigrator.php
│   └── *.sql
├── tests/
│   ├── Pest.php
│   ├── TestCase.php
│   ├── Support/{TestDatabase.php, FakeBoard.php}
│   ├── Unit/{LevelTest.php, PermissionsForLevelTest.php}
│   └── Feature/{ResolverTest.php, AccessManagerTest.php, FacadeTest.php, AccessibleResourcesTest.php, SeederTest.php}
├── docs/                             # site VitePress (ver seção Documentação)
├── .github/workflows/{ci.yml, docs.yml}
├── phpunit.xml
└── README.md
```

## composer.json (essencial)

```json
{
  "name": "gustavocabreira/rbac",
  "description": "Resource-scoped RBAC for any PHP system (PDO/MySQL, framework-agnostic).",
  "type": "library",
  "require": { "php": ">=8.0", "ext-pdo": "*", "ext-pdo_mysql": "*" },
  "require-dev": { "pestphp/pest": "^1.21", "mockery/mockery": "^1.4" },
  "autoload": { "psr-4": { "Huggy\\Rbac\\": "src/" } },
  "autoload-dev": { "psr-4": { "Huggy\\Rbac\\Tests\\": "tests/", "Huggy\\Rbac\\Database\\": "database/" } },
  "config": { "allow-plugins": { "pestphp/pest-plugin": true } },
  "minimum-stability": "stable"
}
```

> Pest 2+ exige PHP 8.1+. Para garantir a suite em **PHP 8.0**, usar **Pest 1.x**.

## Ambiente Docker (PHP-FPM + MySQL de teste)

`docker/php/Dockerfile`:
- `FROM php:8.0-fpm`
- Instalar `pdo`, `pdo_mysql` (`docker-php-ext-install pdo pdo_mysql`) e `pcov` (PECL, cobertura).
- Composer (`COPY --from=composer:latest /usr/bin/composer /usr/bin/composer`).
- `WORKDIR /app`.

`docker-compose.yml`:
- Serviço `php` (build do Dockerfile), volume `.:/app`, depende de `mysql`.
- Serviço `mysql` (`mysql:8`): `MYSQL_DATABASE=rbac_test`, `MYSQL_ROOT_PASSWORD=secret`,
  com healthcheck. **Banco de teste descartável** — nunca aponta para produção.
- Variáveis de conexão do teste via env (host `mysql`, db `rbac_test`).

`Makefile`:
```
build:    docker compose build
up:       docker compose up -d mysql
install:  docker compose run --rm php composer install
test:     docker compose run --rm php vendor/bin/pest
coverage: docker compose run --rm php vendor/bin/pest --coverage
docs:     docker compose run --rm php sh -c "cd docs && npm ci && npm run docs:build"
```

## Documentação (site)

Gerar um **site de docs bonito**, no estilo dos pacotes modernos, em `docs/` usando **VitePress**
(alternativa aceitável: MkDocs Material). Requisitos:

- Navegação lateral + topo, **busca** integrada, **tema claro/escuro**, code blocks com highlight
  e botão de copiar, responsivo.
- **Deploy automático no GitHub Pages** via GitHub Actions (`.github/workflows/docs.yml`) a cada
  push na branch principal.
- Home (hero) com pitch do pacote, badges (versão, PHP, testes) e CTA "Começar".
- **Páginas mínimas** (reaproveitar os snippets da seção "Exemplos de uso"):
  1. **Introdução** — o que é RBAC resource-scoped, acesso direto vs via grupo, multi-tenant.
  2. **Instalação** — `composer require gustavocabreira/rbac`, requisitos (PHP 8.0, `pdo_mysql`).
  3. **Configuração** — `Rbac::configure($pdo)` no bootstrap e `Rbac::forCompany()` no middleware.
  4. **Conceitos** — módulos, roles (owner/admin/viewer), níveis, permissões, mapa role→permissões.
  5. **Conceder** — `assign` pelo ator e `grant` pelo recurso (agente e grupo).
  6. **Revogar** — `revoke` nos dois ângulos.
  7. **Checar** — `can`/`hasRole`/`role`/`permissions`/`allows`.
  8. **Listagem de recursos** — `accessibleFor` / `accessibleIdsFor` e a trait.
  9. **Interface `Permissionable` + trait `IsPermissionable`** — convenção e overrides.
  10. **Referência da API** — assinaturas de `Rbac`, `CompanyAgent`, `HuggyRole`, `PermissionResolver`, `AccessManager`.
  11. **Testes** — como rodar a suite no Docker.
- Incluir um link "Edit this page" e seção de FAQ (ex.: "como mapear um módulo com nome diferente
  da classe?", "como auditar quem concedeu?").

## Testes (Pest, dados mockados sobre MySQL descartável)

**Setup do banco de teste:** `tests/Support/TestDatabase.php` cria um `PDO` MySQL apontando para
o serviço `mysql` (db `rbac_test`), roda `LocalMigrator::migrate()` para criar as 8 tabelas e
oferece helpers para semear **fixtures** (definições via `DefinitionSeeder` + linhas em
`tb_Resource_Agent_Access`, `tb_Resource_Role_Access`, `tb_Companies_Agents`). Cada teste roda em
**transação** (begin no setup, rollback no teardown) ou trunca as tabelas — isolamento total.
`Pest.php`/`TestCase` chamam `Rbac::configure($pdo)` + `Rbac::forCompany($c)` no setup e
`Rbac::reset()` + `flush()` no teardown. **Nenhuma conexão a banco de produção** — fixtures são os
"dados mockados".

**Unit:** lógica pura (`Level`, `permissionsForLevel`) pode usar `PDO`/`PDOStatement` mockados
(Mockery) para validar o shape de query sem subir banco.

**Cenários obrigatórios (espelhar `ModulePermissionsTest`, ~23 casos):**
1. Agente sem acesso → nível 0, `resolve` vazio, `can` false.
2. Viewer → só `board.view`.
3. Admin → múltiplas permissões; **sem** `folder.delete`.
4. Owner → todas.
5. Merge: direto=viewer + grupo=admin → **admin** (maior nível vence).
6. `assign` upsert (2x não duplica) — valida `ON DUPLICATE KEY UPDATE`.
7. `revoke` remove o acesso.
8. `can('edit', $board)` resolve `board.edit` e delega corretamente.
9. `grant`/`revoke` pelos dois ângulos (ator e recurso), agente e grupo.
10. Propagação via grupo: `tb_Companies_Agents` → `tb_Resource_Role_Access`.
11. Módulo `folder` (regras distintas).
12. `agents()` e `roles()` (listagens no recurso).
13. `accessibleIdsFor()` e `accessibleResources()` agrupado.
14. **Trait/interface:** `FakeBoard implements Permissionable { use IsPermissionable; }` com
    `rbacQueryByIds` filtrando fixtures; `FakeBoard::accessibleFor()` retorna só os acessíveis e
    `accessibleIdsFor()` bate com o resolver. Verificar inferência do módulo pelo nome da classe.
15. `DefinitionSeeder` idempotente.

## Critérios de aceite

- [ ] `composer install` + `vendor/bin/pest` verdes **no container php:8.0-fpm contra o MySQL de teste**.
- [ ] Runtime depende só de `ext-pdo` + `pdo_mysql` (verificar `composer.json`).
- [ ] Zero uso de enums/`readonly`/`never` (PHP 8.0).
- [ ] SQL do resolver idêntico ao verbatim (incl. JOIN em `tb_Companies_Agents`); upsert via `ON DUPLICATE KEY UPDATE`.
- [ ] **Nada** no pacote cria/altera schema em produção; migrations ficam em `database/migrations-local/` e são claramente LOCAL/TEST ONLY.
- [ ] As migrations locais recriam as 8 tabelas (com uniques/índices) no MySQL de teste.
- [ ] Interface `Permissionable` + trait `IsPermissionable` (convenção por nome de classe, overrides) funcionando; `Resource` VO também implementa a interface.
- [ ] Fachada fluente (`Rbac::agent()/role()/resource()`, `CompanyAgent`, `HuggyRole`) cobrindo conceder/revogar/checar nos dois ângulos.
- [ ] Os ~23 cenários cobertos e passando.
- [ ] Site de docs (VitePress) buildando e com deploy no GitHub Pages configurado.
- [ ] README com início rápido: `Rbac::configure` → `forCompany` → grant → `can()` → `Model::accessibleFor()`.
- [ ] Um dev constrói o pacote **só com este prompt**, sem o código-fonte original.
