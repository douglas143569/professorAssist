# Assistente para Professores

Aplicação web que ajuda o professor a **preparar aula**: gera conteúdo, planos de
aula, atividades, banco de questões e folhas imprimíveis com apoio de IA
(Claude / Anthropic), sempre com o professor revisando e aprovando antes de usar.

Construído em PHP 8.3 + MySQL 8.4 sobre um micro-framework MVC próprio.

## Princípios do projeto

- **Só o professor usa o sistema.** Não existe portal do aluno e nenhum dado
  pessoal de menor de idade é armazenado — provas e atividades saem em PDF.
- **Human-in-the-loop.** Tudo que a IA gera nasce como `rascunho` e só vira
  `aprovado` depois que o professor revisa e edita.
- **Alinhado à BNCC.** Ensino Fundamental/Médio usa códigos de habilidade
  (ex: `EF67LP08`); Educação Infantil usa campos de experiência e faixa etária.

## Funcionalidades

Hierarquia pedagógica: **Escola → Turma → Matéria → Tema da aula**.

| Módulo | O que faz |
| --- | --- |
| Login | Autenticação por e-mail e senha (bcrypt), papéis `admin`/`professor`, bloqueio por tentativas e auditoria de acesso |
| Administração de contas | Tela do admin: cria contas, ativa/desativa acesso, promove a admin e redefine senhas |
| Controle de custo da IA | Cache de prompts, teto de gasto por professor (`AI_TETO_USD`) e consumo visível no painel |
| Escolas / Turmas / Matérias | Organização das aulas do professor (só dado institucional) |
| Conteúdo do tema | Geração do conteúdo da aula com IA, editável e aprovável |
| Plano de aula | Objetivos, metodologia (introdução/desenvolvimento/fechamento), recursos e avaliação |
| Banco de questões | Geração em lote, alternativas, dificuldade, habilidade BNCC, filtros |
| Gerador de provas | Monta sorteando do banco por dificuldade, ajuste manual de ordem e pontuação, versões embaralhadas (A/B/C/D) com gabarito próprio e cartão-resposta |
| Atividade impressa | Folha de exercícios do tema, numerada, com versão gabarito |
| Atividades sugeridas | Formatos variados (individual, grupo, discussão, prática, projeto, jogo) |
| Calendário | Provas, trabalhos, lembretes e aulas, com aba de eventos criados |
| Creche (Ed. Infantil) | Atividades lúdicas, cronograma semanal e pacotes de folhas imprimíveis |

As folhas da creche saem em 5 formatos (`escrever`, `ligar`, `pintar`,
`sequencia`, `circular`), prontas para imprimir ou salvar em PDF.

## Stack

- PHP 8.3, MySQL 8.4, Composer
- SDK oficial `anthropic-ai/sdk` + `guzzlehttp/guzzle`
- Apache (ou o servidor embutido do PHP para desenvolvimento)
- Sem framework de front-end: PHP nas views + CSS próprio (`public/assets/css/app.css`)

## Estrutura de pastas

```
app/
  Controllers/   -> Controllers (HTTP)
  Models/        -> Models (acesso a dados)
  Views/         -> Views (HTML/PHP)
  Core/          -> Router, Controller/Model base, Database (PDO), Request
  Services/      -> Regras de negócio e integrações (AI, Logger)
routes/web.php   -> Definição de rotas
database/
  migrations/    -> Scripts .sql versionados
  migrate.php    -> Executa as migrations pendentes
public/          -> Document root (index.php, css, js, imagens)
storage/         -> Logs e cache (não versionado)
```

## Como rodar localmente

1. Instale as dependências (isso também ativa o hook de proteção da `main`):
   ```
   composer install
   ```
2. Copie o ambiente e ajuste banco e chave de API:
   ```
   copy .env.example .env
   ```
   Preencha `ANTHROPIC_API_KEY` com a sua chave. O `.env` **nunca** é commitado.
3. Crie o banco `app` e aplique as migrations:
   ```
   composer migrate
   ```
4. Crie a conta administradora (a senha é digitada na hora, nunca fica no repo):
   ```
   php database/criar_admin.php "Seu Nome" voce@dominio.com
   ```
5. Suba o servidor de desenvolvimento:
   ```
   php -S 127.0.0.1:8000 -t public router.php
   ```
   O `router.php` é necessário porque o servidor embutido do PHP não lê o
   `.htaccess` e não sabe rotear URLs amigáveis sozinho. Sob Apache/Laragon,
   o `.htaccess` já resolve e o `router.php` não é usado.

## Estado atual

Funcionando: login com papéis, escolas, turmas, matérias, temas, conteúdo com
IA, planos de aula, banco de questões, gerador de provas, atividades
sugeridas, atividade impressa, calendário e o módulo completo da creche
(atividades lúdicas, cronograma semanal, pacotes de folhas imprimíveis).

Pendente: rubricas digitais, renderização de markdown nas telas de conteúdo,
paginação nas listagens e testes automatizados.

O script `database/criar_admin.php` continua sendo o caminho para a **primeira**
conta administradora (quando ainda não há ninguém para acessar a tela). Daí em
diante, as contas são gerenciadas em **Contas**, no menu.

Fora de escopo por decisão de projeto: correção automática com nota por aluno.
Ela exigiria cadastrar alunos e guardar suas respostas — dado pessoal de menor
de idade. A correção é feita pelo professor com o gabarito de cada versão.

## Fluxo de trabalho no Git

- `main` -> produção. Push direto é **bloqueado** pelo hook `.githooks/pre-push`.
- `dev` -> desenvolvimento (trabalho do dia a dia).
- Feature nova: branch `feature/nome` a partir de `dev` -> Pull Request para `dev`.
- Publicar: Pull Request de `dev` -> `main`.

Consulte [CLAUDE.md](CLAUDE.md) para as convenções completas (camadas, logging,
migrations, segurança e commits).
