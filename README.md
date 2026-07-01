# PHP MVC Starter

Base **profissional** para iniciar projetos em PHP + MySQL. Já vem com um
micro-framework MVC próprio, migrations versionadas e um padrão de
**logging/auditoria** pronto para usar. É o ponto de partida — clone, renomeie
e comece a construir sua aplicação em cima.

## O que já vem pronto

- MVC próprio (Router, Controller/Model base, Database via PDO)
- Autoload PSR-4 via Composer
- Configuração por ambiente (`.env` com phpdotenv)
- Sistema de **migrations** versionadas (`database/migrations`)
- Padrão de **logging e auditoria** (`App\Services\Logger` + tabelas
  `activity_logs` e `access_logs`)
- Fluxo Git com branches `dev`/`main` e hook que bloqueia push direto na `main`
- Convenções documentadas em [CLAUDE.md](CLAUDE.md)

## Stack

- PHP 8.1+
- MySQL 5.7+ / 8.x
- Composer
- Apache (ou o servidor embutido do PHP para desenvolvimento)

## Estrutura de pastas

```
app/
  Controllers/   -> Controllers (HTTP)
  Models/        -> Models (acesso a dados)
  Views/         -> Views (HTML/PHP)
  Core/          -> Router, Controller/Model base, Database (PDO), Request
  Services/      -> Regras de negócio / integrações (ex: Logger)
config/          -> Configurações da aplicação
routes/web.php   -> Definição de rotas
database/
  migrations/    -> Scripts .sql versionados
  migrate.php    -> Executa as migrations pendentes
public/          -> Document root (index.php, css, js, imagens)
storage/         -> Logs e cache (não versionado)
```

## Como começar um projeto novo a partir daqui

1. Clone e renomeie a pasta para o seu projeto.
2. Copie o ambiente e ajuste os dados do banco:
   ```
   copy .env.example .env
   ```
3. Instale as dependências (isso também ativa o hook de proteção da `main`):
   ```
   composer install
   ```
4. Crie o banco e as tabelas base:
   ```
   composer migrate
   ```
5. Suba localmente para testar:
   ```
   php -S 127.0.0.1:8000 -t public
   ```
6. Ajuste `composer.json` (`name`), `.env.example` e o conteúdo de `Views`
   para a sua aplicação, e comece a codar.

## Fluxo de trabalho no Git

- `main` -> produção. Push direto é **bloqueado** pelo hook `.githooks/pre-push`.
- `dev` -> desenvolvimento (trabalho do dia a dia).
- Feature nova: branch `feature/nome` a partir de `dev` -> Pull Request para `dev`.
- Publicar: Pull Request de `dev` -> `main`.

Consulte [CLAUDE.md](CLAUDE.md) para as convenções completas (camadas, logging,
migrations, segurança e commits).
