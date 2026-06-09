# Registrando Módulos e Permissões

Antes de conceder acesso a qualquer recurso, o módulo e suas permissões precisam existir no banco de dados. O `DefinitionSeeder` é a ferramenta para isso — ele é **idempotente** (seguro para executar múltiplas vezes) e usa `ON DUPLICATE KEY UPDATE` internamente.

## Visão geral

O pipeline completo de seeding tem quatro etapas que devem ser executadas em ordem:

1. Registrar o **módulo** (`tb_Modules`)
2. Registrar os **module roles** com seus níveis (`tb_Module_Roles`)
3. Registrar as **permissões** para cada módulo (`tb_Permissions`)
4. Mapear **quais permissões cada role concede** (`tb_Module_Role_Permissions`)

## Usando o `DefinitionSeeder`

```php
use GustavoCabreira\Rbac\Connection;
use GustavoCabreira\Rbac\Seeding\DefinitionSeeder;

$seeder = new DefinitionSeeder(new Connection($pdo));
$seeder->run(); // seed dos módulos board + folder com todos os roles e permissões
```

`run()` é o ponto de entrada. Chame durante o bootstrap da aplicação, migration ou um comando CLI dedicado.

## Adicionando um novo módulo

Para registrar um módulo customizado (ex.: `pipeline`), insira em `tb_Modules`:

```php
$conn = new Connection($pdo);

// 1. Registrar o módulo
$conn->statement(
    'INSERT INTO tb_Modules (modModuleSlug, modModuleName)
     VALUES (?, ?)
     ON DUPLICATE KEY UPDATE modModuleName = VALUES(modModuleName)',
    ['pipeline', 'Pipeline']
);
```

## Registrando permissões para um módulo

Cada slug de permissão segue a convenção `{módulo}.{ação}`:

```php
// 2. Buscar o ID do módulo criado
$module = $conn->selectOne(
    'SELECT modModuleID FROM tb_Modules WHERE modModuleSlug = ?',
    ['pipeline']
);

$moduleId = (int) $module['modModuleID'];

// 3. Inserir as permissões
$permissions = ['pipeline.view', 'pipeline.create', 'pipeline.edit', 'pipeline.delete'];

foreach ($permissions as $slug) {
    $name = ucfirst(str_replace('.', ' ', $slug)); // "Pipeline view", etc.

    $conn->statement(
        'INSERT INTO tb_Permissions (perPermissionModuleID, perPermissionName, perPermissionSlug)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE perPermissionName = VALUES(perPermissionName)',
        [$moduleId, $name, $slug]
    );
}
```

## Linkando permissões aos roles

Após as permissões existirem, mapeie-as para os module roles (`owner`, `admin`, `viewer`):

```php
// 4. Definir quais permissões cada role recebe
$map = [
    'owner'  => ['pipeline.view', 'pipeline.create', 'pipeline.edit', 'pipeline.delete'],
    'admin'  => ['pipeline.view', 'pipeline.create', 'pipeline.edit'],
    'viewer' => ['pipeline.view'],
];

foreach ($map as $roleSlug => $permSlugs) {
    $role = $conn->selectOne(
        'SELECT mroModuleRoleID FROM tb_Module_Roles WHERE mroModuleRoleSlug = ?',
        [$roleSlug]
    );

    foreach ($permSlugs as $permSlug) {
        $perm = $conn->selectOne(
            'SELECT perPermissionID FROM tb_Permissions WHERE perPermissionSlug = ?',
            [$permSlug]
        );

        $conn->statement(
            'INSERT INTO tb_Module_Role_Permissions
               (mrpModuleRolePermissionRoleID, mrpModuleRolePermissionPermissionID)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE
               mrpModuleRolePermissionRoleID = VALUES(mrpModuleRolePermissionRoleID)',
            [$role['mroModuleRoleID'], $perm['perPermissionID']]
        );
    }
}
```

## Encapsulando em um seeder customizado

Para manutenibilidade, envolva os passos acima em sua própria classe de seeder:

```php
class PipelineSeeder
{
    public function __construct(private Connection $conn) {}

    public function run(): void
    {
        $this->seedModule();
        $this->seedPermissions();
        $this->seedRoleMap();
    }

    // ... métodos privados seguindo o padrão acima
}
```

Chame junto com `DefinitionSeeder::run()` no seu bootstrap.

## Verificando o setup

Após o seeding, confirme que o módulo está pronto verificando que `can()` resolve corretamente assim que um agente recebe um role:

```php
Rbac::access()->assignAgentRole($agentId, Rbac::resource('pipeline', $pipelineId), RoleSlug::ADMIN);

$agent = Rbac::agent($agentId);
$agent->can('pipeline.edit', Rbac::resource('pipeline', $pipelineId));   // true
$agent->can('pipeline.delete', Rbac::resource('pipeline', $pipelineId)); // false — admin não tem delete
```
