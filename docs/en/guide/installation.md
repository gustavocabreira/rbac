# Installation

## Requirements

- PHP **8.0** or higher
- `ext-pdo`
- `ext-pdo_mysql`
- MySQL database (schema managed by your application)

## Install via Composer

```bash
composer require gustavocabreira/rbac
```

## Schema

This package reads and writes an existing MySQL schema. The 8 tables must already exist in your database — the package **never creates or alters tables in production**.

If you are extracting from a legacy Laravel app, the migration already lives there. For a fresh setup, run the provided local migrator once to create the schema.

## Development / test setup

Use the Docker environment provided:

```bash
make build
make up
make install
make test
```

This spins up a disposable MySQL instance and runs the full test suite.
