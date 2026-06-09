# Introdução

`gustavocabreira/rbac` é um pacote de **RBAC com escopo por recurso** para PHP. Permite atribuir roles a agentes (usuários) ou grupos de roles em instâncias específicas de recurso — não apenas globalmente.

## O que é RBAC com escopo por recurso?

O RBAC tradicional atribui um role a um usuário globalmente: _"Alice é admin"_. O RBAC com escopo por recurso adiciona uma dimensão de instância: _"Alice é admin **no board #42**"_.

Cada verificação de permissão recebe três entradas:
- **Agent** — o usuário (identificado por ID inteiro)
- **Módulo** — o tipo do recurso (ex.: `board`, `folder`)
- **Resource ID** — a instância específica (ex.: `42`)

## Acesso direto vs. via grupo

Um agente pode obter acesso de duas formas:

| Caminho | Tabela |
|---|---|
| Direto | `tb_Resource_Agent_Access` |
| Via grupo de role | `tb_Resource_Role_Access` + `tb_Companies_Agents` |

Quando ambos os caminhos existem, o **nível mais alto vence** (máximo entre nível direto e nível do grupo).

## Multi-Tenant

Cada registro de acesso pertence a uma empresa. Defina a empresa ativa uma vez por request:

```php
Rbac::forCompany($companyIdFromRoute);
```

Todas as chamadas subsequentes a `Rbac::agent()`, `Rbac::role()` e aos resolvers usam automaticamente essa empresa.
