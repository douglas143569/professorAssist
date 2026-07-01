# Padrão do projeto — PHP MVC Starter

Este arquivo define as **convenções** que todo código do projeto deve seguir.
Vale para os desenvolvedores e para o assistente de IA. Ao criar algo novo,
siga estes padrões em vez de inventar um estilo próprio.

## Arquitetura (camadas)

```
Request → public/index.php → Router → Controller → Service → Model → Database
                                          ↓
                                        View (HTML)
```

- **Controller** (`app/Controllers`): recebe a requisição, valida entrada,
  chama Services/Models e devolve uma View ou JSON. Não coloque regra de
  negócio pesada aqui.
- **Service** (`app/Services`): regra de negócio e integrações (IA/MCP, e-mail,
  logging). Reutilizável e testável.
- **Model** (`app/Models`): acesso a dados. Uma classe por tabela/agregado.
  Sempre com **prepared statements** (nunca concatene input do usuário em SQL).
- **View** (`app/Views`): só apresentação. Sempre escape saída com
  `htmlspecialchars()`.
- **Core** (`app/Core`): infraestrutura do micro-framework (Router, Controller
  base, Model base, Database, Request). Mexa aqui com parcimônia.

## Logging e auditoria — REGRA IMPORTANTE

Nunca faça `INSERT` direto nas tabelas de log nem escreva em arquivo na mão.
Use **sempre** o serviço `App\Services\Logger`:

```php
use App\Services\Logger;

// Log técnico (arquivo storage/logs/app-AAAA-MM-DD.log)
Logger::info('Mensagem');
Logger::warning('Algo estranho', ['contexto' => $x]);
Logger::error('Deu ruim');
Logger::exception($e);            // dentro de catch

// Auditoria de negócio (tabela activity_logs)
Logger::activity('post.published', [
    'entity_type' => 'post',
    'entity_id'   => $postId,
    'description' => 'Usuário publicou um post',
    'properties'  => ['titulo' => $titulo],   // vira JSON
]);

// Segurança / autenticação (tabela access_logs)
Logger::access('login_success', $email, $userId);
Logger::access('login_failed', $email);     // email inexistente também
```

- `action` em `activity()` segue o padrão `entidade.verbo` (ex:
  `post.created`, `user.updated`, `order.completed`).
- `event` em `access()` só aceita: `login_success`, `login_failed`,
  `logout`, `password_reset`.
- O Logger captura IP e user-agent sozinho e **nunca quebra a aplicação**
  se a escrita falhar.

## Banco de dados / migrations

- Toda mudança de schema é uma **migration nova** em `database/migrations`,
  nomeada `NNNN_descricao.sql` (número sequencial de 4 dígitos). Nunca edite
  uma migration já aplicada — crie outra.
- Rode com `composer migrate` (ou `php database/migrate.php`).
- Convenções de tabela: `InnoDB`, `utf8mb4`, `id` PK auto_increment,
  `created_at`/`updated_at` em `DATETIME`, FKs nomeadas (`fk_...`) e índices
  nomeados (`idx_...`). IP em `VARCHAR(45)` (IPv6).

## Segurança

- Senhas: `password_hash()` / `password_verify()` (nunca em texto puro).
- SQL: sempre prepared statements com bind de parâmetros.
- Saída HTML: sempre `htmlspecialchars()` nas Views.
- Segredos (chaves de API, senha de banco) só no `.env` (nunca commitado).

## Git / fluxo de trabalho

- `dev` = desenvolvimento (trabalho do dia a dia). `main` = produção.
  Push direto na `main` é bloqueado por hook local.
- Feature nova: branch `feature/nome` a partir de `dev` → PR para `dev`.
- Publicar: PR de `dev` → `main`.
- Commits no imperativo com prefixo: `feat:`, `fix:`, `chore:`, `docs:`.

## Comandos úteis

```bash
composer install          # instala deps + ativa hook de proteção da main
composer migrate          # aplica migrations pendentes
php -S 127.0.0.1:8000 -t public   # sobe o app localmente (sem Laragon)
```
