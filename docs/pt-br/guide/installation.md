# Instalação

## Requisitos

- PHP **8.0** ou superior
- `ext-pdo`
- `ext-pdo_mysql`
- Banco de dados MySQL (schema gerenciado pela sua aplicação)

## Instalar via Composer

```bash
composer require gustavocabreira/rbac
```

## Schema

Este pacote lê e escreve em um schema MySQL já existente. As 8 tabelas precisam existir no seu banco — o pacote **nunca cria ou altera tabelas em produção**.

Se você está extraindo de uma aplicação Laravel legada, a migration já existe lá. Para uma instalação nova, execute o migrator local fornecido uma vez para criar o schema.

## Setup de desenvolvimento / teste

Use o ambiente Docker fornecido:

```bash
make build
make up
make install
make test
```

Isso sobe uma instância MySQL descartável e roda a suite de testes completa.
