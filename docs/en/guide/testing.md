# Running Tests

## Prerequisites

- Docker + Docker Compose

## Run the full suite

```bash
# Build the PHP 8.0-fpm container
make build

# Start the disposable MySQL test container
make up

# Install Composer dependencies
make install

# Run tests
make test

# Run with coverage report
make coverage
```

## What happens

1. `TestDatabase::setup()` runs `LocalMigrator::migrate()` to create the 8 tables in `rbac_test`.
2. Each test runs inside a transaction that is rolled back on teardown — no cross-test pollution.
3. `DefinitionSeeder` seeds modules, roles, and permissions at the start of each test.
4. `Rbac::reset()` is called in teardown to clear all static state.

## Environment variables

| Variable | Default | Description |
|---|---|---|
| `DB_HOST` | `mysql` | MySQL host |
| `DB_DATABASE` | `rbac_test` | Database name |
| `DB_USERNAME` | `root` | Username |
| `DB_PASSWORD` | `secret` | Password |

Override via env when running outside Docker:

```bash
DB_HOST=127.0.0.1 vendor/bin/pest
```
