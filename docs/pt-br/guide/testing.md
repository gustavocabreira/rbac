# Rodando os Testes

## Pré-requisitos

- Docker + Docker Compose

## Rodar a suite completa

```bash
# Build do container PHP 8.0-fpm
make build

# Subir o container MySQL descartável de teste
make up

# Instalar dependências do Composer
make install

# Rodar os testes
make test

# Rodar com relatório de cobertura
make coverage
```

## O que acontece

1. `TestDatabase::setup()` executa `LocalMigrator::migrate()` para criar as 8 tabelas em `rbac_test`.
2. Cada teste roda dentro de uma transação que é revertida no teardown — sem poluição entre testes.
3. `DefinitionSeeder` faz o seed de módulos, roles e permissões no início de cada teste.
4. `Rbac::reset()` é chamado no teardown para limpar todo o estado estático.

## Variáveis de ambiente

| Variável | Padrão | Descrição |
|---|---|---|
| `DB_HOST` | `mysql` | Host do MySQL |
| `DB_DATABASE` | `rbac_test` | Nome do banco |
| `DB_USERNAME` | `root` | Usuário |
| `DB_PASSWORD` | `secret` | Senha |

Sobrescreva via env ao rodar fora do Docker:

```bash
DB_HOST=127.0.0.1 vendor/bin/pest
```
