# MT Par

Sistema de gestão de cotações, licitações, demandas e comprovação de vantajosidade
de atas de registro de preços, para um órgão público (MT Participações e Projetos S.A.).
PHP puro (sem framework), roteamento via `index.php`, banco SQLite via PDO.

**O servidor de produção real roda PHP 8.2.12** (XAMPP do usuário). Este sandbox remoto
roda PHP 8.4 — não assuma que a versão local é a versão de produção. `composer.json` trava
isso via `"require": {"php": ">=8.2"}` e `"config.platform.php": "8.2.12"`; não remova essa
trava, ela existe porque já causou um incidente em produção (ver "Composer" abaixo).

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

## Dois computadores (casa e trabalho) — como funciona na prática

O usuário revezar entre dois computadores físicos: um em casa (pessoal) e um no trabalho
(o XAMPP com as duas pastas `mtpar`/`mtpar-teste` da tabela acima). **Esta sessão (e
qualquer sessão de Claude Code rodando "na nuvem"/remota) nunca tem acesso direto a
nenhum desses dois computadores** — ela só enxerga uma cópia do repositório rodando num
sandbox isolado. A única ponte entre o que a sessão faz aqui e o computador físico do
usuário é o GitHub: a sessão só consegue `git push`; o `git pull` no computador físico
(seja casa, seja trabalho) **sempre precisa ser digitado pelo próprio usuário** — nunca
é algo que a sessão "faz por ele" automaticamente, mesmo que ele peça assim.

Combinado com o usuário: quando ele disser algo como "vamos atualizar meu processo em
casa" ou "vamos atualizar o processo do trabalho", isso significa:
1. Terminar/commitar/dar push do que estiver em andamento pra branch certa (`dev`, ou
   `main` só se ele pedir explicitamente).
2. Devolver pra ele o(s) comando(s) exatos de `git pull` pra rodar no computador daquele
   local especificamente — em casa normalmente só a pasta de teste (`mtpar-teste`,
   branch `dev`); no trabalho, a pasta relevante (`mtpar-teste` para testar, `mtpar`
   só quando for promoção pra produção).

Sempre que pedir isso, dê os comandos completos e explícitos (o usuário já avisou que é
leigo em terminal) — nunca assuma que ele lembra o caminho da pasta ou o comando exato.

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

## Composer — cuidado ao commitar vendor/

Este projeto commita `vendor/` no Git (decisão do projeto, produção não roda `composer
install`, só `git pull`). Mas `phpunit/phpunit` e suas dependências (`require-dev`) ficam
de fora de propósito (`.gitignore`) por serem pesadas.

**Isso significa que os arquivos de autoload (`vendor/composer/autoload_*.php`) SÓ podem
ser commitados no estado gerado por `composer install --no-dev`** — nunca o `composer
install`/`composer require` normal (com dev). Se o mapa de autoload referenciar uma classe
de um pacote de `require-dev`, qualquer pasta que só fez `git pull` (produção, teste) quebra
com fatal error ao carregar `vendor/autoload.php`, porque o arquivo listado no mapa não
existe ali — isso já aconteceu de verdade e derrubou a geração de relatório Word em produção.

Fluxo correto depois de qualquer `composer require`/`composer update`/rodar os testes:
```
composer install --no-dev
git status   # deve mostrar só a diferença esperada, sem vendor/phpunit ou similares voltando
```
Só commitar depois disso. Pra rodar os testes de novo localmente, `composer install` (sem
`--no-dev`) reinstala o PHPUnit temporariamente — não commitar nesse estado intermediário.

## Idioma

O usuário se comunica em português. Responda em português.
