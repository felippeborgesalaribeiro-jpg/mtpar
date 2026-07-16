# MT Par

Sistema de gestão de cotações, licitações, demandas e comprovação de vantajosidade
de atas de registro de preços, para um órgão público (MT Participações e Projetos S.A.).
PHP 8.4 puro (sem framework), roteamento via `index.php`, banco SQLite via PDO.

## Fluxo de branches — leia antes de commitar ou dar push

Este projeto está **em uso real** por pessoas do setor (não é só um repositório de testes).
Existem dois ambientes:

| Branch | Pasta local (Windows/XAMPP) | Uso |
|---|---|---|
| `main` | `C:\xampp\htdocs\mtpar` | **Produção.** Acessado pelos colegas de trabalho via rede local. |
| `dev` | `C:\xampp\htdocs\mtpar-teste` | **Teste.** Onde mudanças são validadas antes de ir pra produção. |

**Regra padrão: todo trabalho novo (commits, correções, features) vai para `dev`, nunca
direto para `main`.** Só faça merge de `dev` para `main` (ou dê push direto em `main`)
quando o usuário pedir explicitamente algo como "pode subir pra produção", "promove pra
main" ou equivalente — nunca por iniciativa própria, mesmo que a mudança pareça pequena
ou óbvia.

Isso vale tanto para esta sessão remota quanto para qualquer sessão local do Claude Code
(VS Code) rodando no repositório.

## Banco de dados

`database/mtpar.sqlite` é **versionado no Git** e contém dados reais (servidores,
cotações, demandas). Isso é uma decisão já tomada pelo projeto — não é para "corrigir"
sem que o usuário peça. Ao trabalhar com esse arquivo, ter em mente que qualquer commit
que o toque grava uma cópia dos dados reais no histórico do Git.

`database/schema.sql` é a fonte única de verdade do schema atual (consolida as migrações
históricas em `database/migrate_*.php`, que não devem ser re-executadas — eram scripts
de uso único).

## Testes

O projeto tem uma suíte PHPUnit em `tests/`. Rodar com:

```
composer install   # primeira vez, baixa o PHPUnit (não é commitado, ver .gitignore)
composer test
```

Os testes usam um banco SQLite temporário (via `Database::usePath()`), nunca o
`database/mtpar.sqlite` real. Antes de mudanças arriscadas em models/controllers
(especialmente lógica de exclusão/restauração, autenticação, ou qualquer refatoração
estrutural), escreva ou rode os testes relevantes para confirmar que nada quebrou —
o projeto não tem CI, então essa é a única rede de segurança automatizada que existe.

## Idioma

O usuário se comunica em português. Responda em português.
