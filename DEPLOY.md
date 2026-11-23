# Deploy (VPS)

Requisitos: PHP 8+, Composer, MySQL, Nginx ou Apache apontando para `public/`.

## Primeira instalação

1. `git clone -b dev https://github.com/douglas143569/professorAssist.git` (ou a branch de produção definida pela equipe).
2. `composer install --no-dev -o`
3. `cp .env.example .env` e preencher: `APP_ENV=production`, `APP_URL` (https), `DB_*`, chave da IA, Mercado Pago, `MAIL_*` (SMTP) e `LGPD_*`.
4. Servidor web com a raiz em `public/` (nunca na raiz do projeto).
5. Dar escrita ao usuário do servidor web em `storage/`.
6. `php database/migrate.php` (cria o banco e as tabelas).
7. Criar o administrador: `php database/criar_admin.php "Nome" email@dominio.com` (a senha é digitada na hora; nunca fica no repositório).

## Atualização

```bash
git pull
composer install --no-dev -o
php database/migrate.php   # aplica só as migrations novas
```

Recarregue o PHP-FPM se usar OPcache. O `.env` não vem do git e não é alterado pelo `pull`.
