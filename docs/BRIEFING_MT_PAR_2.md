# Briefing para MT Par 2.0 — Documento de Handover

**Objetivo deste documento:** dar a um novo Claude (ou desenvolvedor) tudo
que ele precisa saber para começar o projeto **MT Par 2.0** do zero, com
stack moderna, sem repetir os erros do V1 e preservando o conhecimento
de negócio que já custou dois anos pra ser mapeado.

**Como usar este documento:** leia da Parte 1 até a Parte 9 antes de
propor qualquer arquitetura. Ao final tem o prompt sugerido pra iniciar
a conversa com um novo Claude.

**Documentos que acompanham este briefing** (todos em `docs/`):

- `AUDITORIA.md` — auditoria completa do V1, com todos os casos de uso
  formalizados, requisitos funcionais e não funcionais, regras de negócio
  e mapa de rotas. **É a fonte de verdade do que o V1 faz hoje.**
- `PESQUISA_PRECO_REFATORAMENTO.md` — mergulho técnico no módulo mais
  crítico (pesquisa de preço 70/30), com análise dos problemas e desenho
  da arquitetura correta. **É a lição mais valiosa do V1 para o V2.**
- `database/schema.sql` — schema SQLite atual (19 tabelas), fonte de
  verdade do modelo de dados que precisa migrar para o V2.

---

## Sumário

- [Parte 1 — Contexto de negócio](#parte-1--contexto-de-negócio)
- [Parte 2 — Resumo executivo do V1](#parte-2--resumo-executivo-do-v1)
- [Parte 3 — Domínio do negócio](#parte-3--domínio-do-negócio)
- [Parte 4 — Regras de negócio críticas](#parte-4--regras-de-negócio-críticas)
- [Parte 5 — Lições aprendidas do V1 (não repetir)](#parte-5--lições-aprendidas-do-v1-não-repetir)
- [Parte 6 — Visão para o V2](#parte-6--visão-para-o-v2)
- [Parte 7 — Decisões técnicas em aberto](#parte-7--decisões-técnicas-em-aberto)
- [Parte 8 — Estratégia de migração e coexistência](#parte-8--estratégia-de-migração-e-coexistência)
- [Parte 9 — Como iniciar o novo chat](#parte-9--como-iniciar-o-novo-chat)

---

## Parte 1 — Contexto de negócio

### 1.1 O cliente

**MT Participações e Projetos S.A. (MT Par)** — sociedade de economia
mista/órgão público do estado. O sistema é usado internamente pelo
**setor de licitações** dela.

### 1.2 Quem usa

- **Servidores comuns** do setor de licitações — cadastram demandas,
  fazem pesquisa de preço, geram documentos oficiais (Análise Crítica,
  Termo de Adjudicação e Homologação, Comparação de Proposta), acompanham
  processos.
- **Administrador do sistema** — gerencia usuários (servidores), lixeira,
  backup, cadastros auxiliares (parâmetros de pesquisa, setores
  demandantes).
- **Volume aproximado:** poucas dezenas de servidores no total, com uso
  concorrente diário na casa de 5-10 pessoas. **Não é sistema massivo.**

### 1.3 Onde roda

- **Rede local** do órgão. Não é público na internet.
- **XAMPP** (Apache + PHP 8.2 + SQLite) numa máquina local do setor.
- **Dois ambientes** hoje: produção (`mtpar/`, branch `main`) e teste
  (`mtpar-teste/`, branch `dev`).

### 1.4 O que o sistema não faz (fora de escopo)

- Não emite nota fiscal nem gerencia execução do contrato depois da
  homologação.
- Não publica em Diário Oficial nem no SIGADOC — só guarda o link.
- Não integra com sistemas externos (SICAF, ComprasNet, TCU).
- Não faz financeiro/empenho.
- Não faz assinatura eletrônica dos documentos. O `.docx` gerado é
  assinado à parte.

### 1.5 Marco legal aplicável

O núcleo da análise 70/30 é baseado em:
- **Art. 9º, §2º do RILC/MTPAR** (Regulamento Interno de Licitações e
  Contratos da MT Par) — exige mínimo de 3 preços aprovados.
- **Art. 47 do Decreto Estadual nº 1.525/2022** — define os critérios
  numéricos da análise (30% acima da média = excessivo; abaixo de 70% =
  inexequível).

Esses são requisitos legais **inegociáveis**. O sistema tem que
implementar exatamente essa análise.

---

## Parte 2 — Resumo executivo do V1

**Stack atual:** PHP 8.2 puro (sem framework), SQLite via PDO, roteamento
por `switch/case` num `index.php`, Bootstrap 5 + JS puro, PhpWord para
gerar `.docx`.

**Escala:** 90 rotas, 20 controllers, 30 models, 25 views, 19 tabelas no
banco, ~13.800 linhas de PHP, 95 testes PHPUnit passando.

**O que funciona bem no V1** (vale preservar como referência de UX no
V2):
- Fluxo completo Demanda → Cotação → Licitação → Termo de Adjudicação.
- Análise 70/30 dos preços (motor `AnalisePrecos`) — a lógica em si é
  correta, o problema é onde ela é usada.
- Widget de empresa vencedora com busca AJAX + cadastro inline.
- Gestão de lotes fracassados/desertos com republicação encadeada.
- Soft-delete padronizado + lixeira do admin com restauração.
- CSRF centralizado, rate-limit de login, autenticação por sessão.

**O que está quebrado ou pesa** (motivo real do V2):
- **Cálculos distribuídos:** o motor de análise 70/30 é chamado em 7
  pontos diferentes do sistema para a mesma cotação. Já causou prejuízo
  financeiro em uma licitação real. Detalhado em
  `PESQUISA_PRECO_REFATORAMENTO.md`.
- **Sem log de auditoria:** não dá pra saber quem alterou o quê, quando.
  Para órgão público isso é grave.
- **Ausência de módulo de Contratos:** falta o pós-homologação.
- **Aplic é só uma flag** ("enviado/não enviado"), não é integração real
  nem tem estado significativo.
- **Relatórios são todos hardcoded** — não dá pra fazer relatório
  personalizado.
- **Migrações manuais** (arquivos `migrate_00X.php`), sem rastreabilidade
  de qual foi rodada onde.
- **Vendor no Git** (decisão histórica pra evitar `composer install` em
  produção) — atrapalha muito o versionamento.

**Bugs corrigidos em produção nesta sessão** (para não confundir com
bugs pendentes):
- Truncamento silencioso do parser de moeda (`1.234,56` virava `1.234`).
- Arredondamento do centavo fantasma (`1031,67 × 4 = 4126,67` em vez de
  `4126,68`).
- CSRF ausente no AJAX de cadastro de empresa.
- Trava de troca de empresa quando CNPJ já existia no banco.
- Alinhamento da soma no Mapa, Cotação, Termo, Conferência de Proposta.

**Bugs identificados em produção mas ainda NÃO corrigidos em `main`:**
- Trava de duplicidade de número de processo (existe em `dev`).
- Filtro de licitação órfã depois de excluir Demanda (existe em `dev`).

---

## Parte 3 — Domínio do negócio

### 3.1 Fluxo principal (Licitação por Pesquisa de Preço)

```
Setor recebe demanda de compra
        │
        ▼
[Demanda cadastrada]  ─── número do processo, setor, objeto, servidor
        │
        ▼
[Cotação (Pesquisa de Preço) vinculada à Demanda]
        │
        │  Servidor cadastra Lotes → Itens → Preços coletados
        │  Cada preço tem uma Fonte (parâmetro: BPS, Painel de Preços, etc.)
        │
        ▼
[Análise 70/30 dos preços de cada item]
        │
        │  Etapa 1: se preço > média_dos_outros × 1.30, é EXCESSIVO
        │  Etapa 2: se preço < média × 0.70, é INEXEQUÍVEL
        │           (exceção: fontes marcadas como "preço público")
        │
        ▼
[Mapa Comparativo] — visão final do que vai virar o valor de referência
        │
        ▼
[Cotação FINALIZADA]  ─── quando ≥ 3 preços aprovados por item
        │
        ▼
[Análise Crítica gerada em .docx] — vai para o processo administrativo
        │
        ▼
[Demanda CONCLUÍDA] ─── dispara criação automática da Licitação
        │
        ▼
[Licitação vinculada à Demanda]  ─── herda valores, servidor, objeto
        │
        │  Servidor edita: edital, sessão pública, valor adjudicado,
        │  encaminhamento contratual, etc.
        │
        ▼
[Conferência de Proposta Vencedora]
        │
        │  Por lote: seleciona/cadastra Empresa vencedora
        │  Por item: digita valor unitário proposto pelo licitante
        │  Alternativa: marca lote como FRACASSADO/DESERTO
        │  Se fracassado/deserto: pode republicar (cria Cotação-filha "R2")
        │
        ▼
[Termo de Adjudicação e Homologação em .docx]
        │
        │  Só emite se todos os lotes têm resolução (vencedor OU fracasso)
        │  Ao gerar, marca a data de adjudicação → Licitação HOMOLOGADA
        │
        ▼
[Aplic] — status "Pendente" → clique manual → "Enviado"
```

### 3.2 Fluxo alternativo (Vantajosidade — adesão a Ata)

Quando o órgão vai ADERIR a uma Ata de Registro de Preços existente (não
faz licitação nova), o fluxo é:

```
[Demanda cadastrada]
        │
        ▼
[Processo de Vantajosidade vinculado à Demanda]
        │
        │  Tipo ATA: número da Ata da qual está aderindo
        │  Tipo CONTRATO_ADITIVO: número do contrato + valor total
        │
        ▼
[Itens da Vantajosidade]  ─── preço_ata (o que a Ata cobra)
        │
        ▼
[Preços de mercado por item]  ─── comparação
        │
        ▼
[Análise: preço da Ata é VANTAJOSO?] ─── item a item
        │
        ▼
[Mapa de Vantajosidade]  ─── consolidação
        │
        ▼
[Processo FINALIZADO]
        │
        ▼
[Análise Crítica de Vantajosidade em .docx]
```

Não gera Licitação. Regra específica: aditivo de contrato tem limite
legal de 25% (`ProcessoVantajosidade::LIMITE_LEGAL_ADITIVO_PERCENTUAL`).

### 3.3 Entidades principais e relacionamentos

Ver `database/schema.sql` para a versão SQL completa. Resumo em texto:

- **Demanda** é o "Processo" — entidade central. Guarda número, setor,
  responsável, status.
- **Demanda 1:N Cotação** — uma Demanda pode ter múltiplas Cotações
  (originais + republicações).
- **Demanda 1:N Vantajosidade** — mesma lógica.
- **Demanda 1:1 Licitação** — cada Demanda concluída gera UMA Licitação.
- **Cotação 1:N Lote 1:N Item 1:N Preço.**
- **Licitação 1:N LotePropostaVencedora** — empresa vencedora de cada
  lote (chave: licitacao_id + lote_id).
- **Licitação 1:N ItemPropostaVencedora** — valor proposto por item.
- **Licitação 1:N SituacaoLote** — marca lote como fracassado/deserto.
- **Licitação 1:N RepublicacaoLote** — encadeia lote antigo ↔ cotação
  nova de republicação.
- **Empresa** — cadastro único por CNPJ. Reaproveitável entre licitações.
- **Servidor** — usuário do sistema (nível ADMIN ou COMUM).
- **Parâmetro** — fontes de pesquisa (BPS, Painel de Preços, etc.). Flag
  `preco_publico` marca as que são exceção na análise 70/30.
- **SetorDemandante** — lista mestre para autocomplete (não é FK).

### 3.4 Estados importantes

- **Demanda.status:** EM ANDAMENTO / ELABORAÇÃO DE PESQUISA DE PREÇO /
  ELABORAÇÃO DE TR / ENVIADO PARA PARECER JURÍDICO / FASE DE HABILITAÇÃO
  / CONCLUÍDO / CANCELADO.
- **Cotação.status:** EM_ANDAMENTO / FINALIZADA.
- **Licitação.status** (derivado, não coluna): AGUARDANDO_PUBLICACAO /
  PUBLICADA / HOMOLOGADA / ENCAMINHADA_PARA_CONTRATACAO.
- **Aplic.status** (derivado): NAO_APLICAVEL / PENDENTE / ENVIADO.
- **Vantajosidade.status:** EM_ANDAMENTO / FINALIZADO.
- **Lote na Conferência de Proposta** (derivado): AguardandoJulgamento /
  VencedorDefinido / Fracassado / Deserto / Republicado.

---

## Parte 4 — Regras de negócio críticas

**RN01 — Análise 70/30 (art. 47 Decreto 1.525/2022 + art. 9º RILC/MTPAR)**

- **Etapa 1 — Excessivo:** se `preço_i > (média_dos_demais) × 1.30`, o
  preço é EXCESSIVAMENTE ELEVADO e sai da consolidação.
- **Etapa 2 — Inexequível:** dos que sobraram, se `preço_i <
  (média_dos_demais_restantes) × 0.70`, é INEXEQUÍVEL e sai da
  consolidação.
- **Exceção:** fontes marcadas como "preço público" (BPS, Painel de
  Preços…) nunca caem em INEXEQUÍVEL, viram EXCECAO_PRECO_PUBLICO
  (aprovado com anotação).
- **Consolidação (valor de referência):** média, mediana, menor preço
  ou planilha orçamentária, entre os aprovados. Critério escolhido no
  cabeçalho da Cotação.
- **Mínimo:** ≥ 3 preços aprovados por item (exceto Planilha
  Orçamentária, que dispensa comparação).

**RN02 — Fonte única de verdade Demanda ↔ Licitação**

Identidade do processo (número, setor, servidor, objeto, data de
recebimento) mora **exclusivamente** na Demanda. A Licitação estende a
Demanda com os campos da fase de licitação (edital, homologação, Aplic).
Editar na Demanda propaga automaticamente pra Licitação.

**RN03 — Geração automática de Licitação**

Ao concluir a Demanda, o sistema gera a Licitação com valor estimado
herdado da Cotação. Demandas de Vantajosidade **não** geram Licitação.

**RN04 — Republicação de lote fracassado**

Cria uma Cotação-filha marcada como `eh_republicacao_lote = 1` (invisível
na listagem geral). Numeração: `MTPAR-PRO-XXXX-R2`, `-R3`, etc.

**RN05 — Homologação = gerar o Termo**

Não existe botão separado de "homologar". O ato de gerar o Termo de
Adjudicação e Homologação em `.docx` **é** o que grava a data de
homologação e move a Licitação para o estado HOMOLOGADA.

**RN06 — Todos os lotes precisam ter resolução para gerar Termo**

Cada lote ativo tem que ter OU empresa vencedora salva, OU marcação de
FRACASSADO/DESERTO. Sem resolução, botão de gerar Termo fica bloqueado.

**RN07 — Aplic**

Só faz sentido depois de HOMOLOGADA. Estado inicial: PENDENTE. Vai para
ENVIADO só com clique manual. **Não é integração automática** com sistema
externo — é só marcação de estado interno.

**RN08 — Vantajosidade — Aditivo de contrato**

Limite legal: 25%. Sistema alerta se ultrapassar.

**RN09 — CNPJ único para empresas**

Constraint no banco. Duas empresas não podem compartilhar CNPJ.
Reaproveitamento é encorajado — a mesma empresa aparece em múltiplas
licitações vinculada pelo CNPJ.

**RN10 — Rate-limit de login**

Máximo 5 tentativas em 15 minutos por identificador de usuário.

**RN11 — Senha provisória**

Todo servidor novo cadastrado tem `senha_provisoria = 1`. Primeiro login
força redefinição.

**RN12 — Soft-delete**

Demanda, Cotação e Vantajosidade usam `deleted_at`. Item excluído vai
para lixeira, admin pode restaurar ou excluir definitivamente.

**RN13 — Números de processo únicos entre demandas ativas**

Não podem existir duas demandas ativas com o mesmo `numero_processo`.
Demandas na lixeira não contam (permite reaproveitar número).

---

## Parte 5 — Lições aprendidas do V1 (não repetir)

### L1. Cálculo distribuído com dinheiro = prejuízo garantido

O V1 tem o motor de análise 70/30 sendo chamado em **7 pontos diferentes
do sistema** para a mesma cotação (Mapa, Cotação-tela, Conferência de
Proposta, Análise Crítica em Word, Comparação de Proposta em Word,
Cotação::calcularValorTotal chamada pela Licitação, view proposta_vencedora
+ JS embutido). Cada ponto decidiu sozinho como arredondar, quando somar,
qual flag passar.

**Consequência real:** licitação com valor errado que causou prejuízo.

**Como o V2 deve resolver:** cálculo **UMA VEZ** no momento do fechamento
da cotação. Grava snapshot com todos os valores derivados (valor de
referência por item, total por item, subtotal por lote, valor global).
Todos os outros lugares LEEM do snapshot. O motor de análise fica
inacessível fora do fluxo de fechamento. Alteração pós-fechamento exige
reabertura explícita e auditada.

Isso está desenhado em detalhe em `PESQUISA_PRECO_REFATORAMENTO.md` —
**é a lição mais importante do V1 e deve ser fundação do V2**.

### L2. Dinheiro em float64 é bomba-relógio

O V1 usa `float` para preços (`REAL` no SQLite). Isso permite
representações binárias imprecisas (`1031.6666...`) que geraram o
"centavo fantasma". Correções foram feitas com `round($v, 2)` espalhadas.

**Como o V2 deve resolver:** value object `Dinheiro` (ou `Money`) que
representa valores como inteiros em centavos. Todas as operações
aritméticas passam pelo tipo. Impossível ter "quebrado" um centavo por
erro numérico.

Libs prontas: `moneyphp/money` no Composer é referência da indústria.

### L3. Sem log de auditoria = irrastreável

Órgão público não pode operar sem saber "quem alterou X, quando, por
quê". O V1 não tem nada disso.

**Como o V2 deve resolver:** tabela de auditoria genérica (ou
event-sourcing simples) que registra todas as ações críticas: quem
excluiu, quem reabriu cotação, quem trocou empresa vencedora, quem
alterou preço em cotação finalizada, etc. Com carimbo de data/hora e
motivo textual quando aplicável.

Laravel tem `laravel-auditing` prontinho.

### L4. Vendor no Git é dívida técnica silenciosa

Foi decisão pragmática do V1 (produção não roda `composer install`, só
`git pull`). Mas trouxe problema real: já causou incidente por autoload
apontando pra pacote de `require-dev` inexistente.

**Como o V2 deve resolver:** deploy adequado. Se for continuar sem CI/CD,
usar Composer Deploy Plugin ou script simples que faz `composer install
--no-dev --optimize-autoloader` no deploy. Não commitar vendor.

### L5. Migrações manuais quebram

Os `migrate_00X.php` do V1 funcionam mas quebram na primeira que assume
um estado que não existe mais no ambiente. Já quebrou em produção.

**Como o V2 deve resolver:** migrations rastreáveis (Laravel Migrations,
Doctrine Migrations, Phinx). Cada migration sabe se já foi rodada.

### L6. Cálculo em JS separado do PHP = drift garantido

A tela de Conferência de Proposta do V1 tem cálculo próprio em
JavaScript. Se o JS e o PHP arredondarem diferente, aparece divergência
"em tempo real na tela vs o que fica salvo".

**Como o V2 deve resolver:** cálculos exclusivamente no backend
(API + preview do backend), ou cálculo em JS reutilizando a MESMA função
que é serializada do backend (ex.: um money.js compartilhado). Se
puder eliminar cálculos client-side, melhor ainda.

### L7. Regras de negócio embutidas em views

O V1 tem lógica de estado da Licitação (`if ($licitacao->estaFinalizada()) ...`)
dentro de arquivos `.php` de view. Difícil testar, fácil esquecer de
propagar.

**Como o V2 deve resolver:** Policy objects / Ability classes. Laravel
tem `Gates` e `Policies` prontos para isso.

### L8. Erros silenciosos são os piores

O bug do "cadastrar empresa não funcionava" acabou sendo um 403 CSRF
silencioso. Fetch antigo ignorava o erro. Ficou dias sem resolver.

**Como o V2 deve resolver:** sempre tratar `!response.ok` em fetches.
Toast/alert padrão do framework quando request falha. Logs de erro
centralizados (Sentry, Bugsnag, ou até só um `log_error` interno).

### L9. Sistema para órgão público precisa de backup automatizado

Backup manual do V1 fica no mesmo disco físico. Se falhar, foi.

**Como o V2 deve resolver:** backup diário automatizado para local
externo (drive de rede, S3, Google Drive, o que couber). Retenção de
30-60 dias.

### L10. UX importa muito quando o usuário é leigo

Os servidores do setor não são desenvolvedores. Mensagens crípticas
(`Fatal error: Class ZipArchive not found`) travavam o setor sem
conseguir explicar. Tudo tem que ser em português claro, com sugestão
de próximo passo.

**Como o V2 deve resolver:** mensagens de erro amigáveis, sempre. Tela
de status/saúde do sistema pra o admin ver se tá tudo ok. Documentação
técnica separada da mensagem que o usuário vê.

---

## Parte 6 — Visão para o V2

### 6.1 Módulos que existem hoje (V1) e continuam no V2

- Autenticação, Perfil, Servidores
- Demandas (Processos)
- Cotações + Pesquisa de Preço (com refatoração de cálculo — L1)
- Licitações
- Vantajosidade
- Proposta Vencedora + Documentos (Análise Crítica, Termo, Comparação)
- Empresas
- Cadastros auxiliares (Parâmetros, Setores Demandantes)
- Admin (Lixeira, Backup)
- Dashboard

### 6.2 Módulos NOVOS planejados para o V2

- **Contratos** — pós-homologação. Guarda contratos assinados, vincula
  a Licitação/Vantajosidade que originou, controla vigência, aditivos,
  valores empenhados vs. executados, prazos.
- **Aplic completo** — não só flag de "enviado", mas dados reais do
  envio, protocolos, prazos, status de análise.
- **Relatórios personalizados** — construtor de relatórios pelo próprio
  usuário. Filtros por período/setor/status, agrupamentos, exportação
  Excel/PDF.
- **Log de auditoria** (L3) — visualização pelo admin.
- **Automações e eventos** — "quando homologar, criar rascunho de
  contrato", "quando contrato vencer, avisar responsável", etc.
- **API interna** — desacopla telas do backend, prepara pra futura
  integração externa.

### 6.3 Stack recomendada

- **Backend:** Laravel 11+ (PHP 8.3+)
- **Frontend inicial:** Blade + Alpine.js + Tailwind (baixa curva,
  rápido de fazer). Migração futura para Livewire ou Inertia se
  precisar de mais interatividade.
- **Banco:** PostgreSQL ou MariaDB. **Não continuar em SQLite** — não
  suporta bem multi-usuário concorrente, e as migrações vão ficar mais
  robustas em RDBMS de verdade.
- **Money:** `moneyphp/money` (value object com precisão de centavos).
- **Auditoria:** `owen-it/laravel-auditing`.
- **Testes:** PHPUnit + Pest.
- **Deploy:** GitHub Actions rodando testes + deploy automatizado (SSH
  ou plataforma tipo Forge/Ploi/Envoyer).
- **Backup:** rotina agendada + envio pra local externo.

### 6.4 Princípios arquiteturais para o V2

- **Uma fonte de verdade por cálculo** — cálculo acontece em UM lugar,
  todos os outros leem (L1).
- **Dinheiro é objeto, não float** (L2).
- **Toda ação crítica gera evento auditado** (L3).
- **Domain-Driven Design leve** — organizar por módulo/contexto, não
  por camada. Módulo Contratos tem seu próprio Controller/Model/Service/
  Policy dentro de `app/Modules/Contratos/`.
- **Regras de negócio em Services, não em Controllers ou Models** —
  Controllers só coordenam HTTP, Models só representam dados.
- **Policies para autorização** — em vez de `if ($usuario->ehAdmin())`
  espalhado.
- **Testes de fluxo (feature tests) + testes unitários dos Services** —
  cobertura maior que o V1 tem.

### 6.5 Fluxo de trabalho novo (bem diferente do V1)

- **Git flow:** main (produção) ← staging ← develop ← feature/*.
- **PRs revisados** (ou pelo próprio dev depois de dormir sobre) antes
  de merge.
- **CI:** testes automáticos em cada PR.
- **Migrations** rastreadas.
- **`composer install --no-dev` em produção** sem vendor no repo.

---

## Parte 7 — Decisões técnicas em aberto

Estas decisões precisam ser tomadas cedo no V2. Cada uma muda partes do
desenho.

**D1. Banco de dados: PostgreSQL ou MariaDB?**
- **PostgreSQL** — melhor em tipos ricos (jsonb, arrays), full-text
  search nativo, migrations mais seguras. Curva um pouco maior.
- **MariaDB** — mais familiar pra maioria dos devs PHP, mais leve pra
  hospedar em servidor modesto do órgão.

**D2. Frontend: Blade + Alpine, Livewire ou Inertia + Vue/React?**
- **Blade + Alpine** — mais simples, muito HTML tradicional, JS leve
  onde precisar. Recomendado no início.
- **Livewire** — components reativos server-side, sem sair do PHP.
  Curva maior mas dá SPA-like sem SPA.
- **Inertia + Vue** — SPA de verdade. Curva bem maior, precisa de dev
  frontend.

**D3. Onde hospedar?**
- Máquina local do setor (continua igual V1)?
- VPS externo (DigitalOcean/Hetzner) com HTTPS e domínio próprio?
- Cloud gerenciada (Laravel Forge, Ploi, Vapor)?

**D4. Estratégia de conta/autenticação:**
- Continuar com login/senha local (como V1)?
- Integrar com AD/LDAP do órgão (se existir)?
- SSO via provedor governamental (gov.br) se aplicável?

**D5. Vantajosidade: fica separado ou vira "modo" da Cotação?**
No V1 são módulos completamente separados com duplicação. No V2, os
dois casos poderiam ser variantes de um mesmo "Processo de Análise de
Preço", com regras diferentes.

**D6. Como lidar com o snapshot da pesquisa de preço (ver
`PESQUISA_PRECO_REFATORAMENTO.md`) no V2?**
No V2 já nasce com snapshot desde o dia 1. Não precisa migrar
gradualmente como no V1.

**D7. Guardar o `.docx` gerado junto ao snapshot?**
Para rastreabilidade total — o Word que foi assinado fica no banco/
storage do sistema, referenciando qual versão do snapshot foi usada.

**D8. Log de auditoria: tabela genérica ou event-sourcing?**
- Tabela genérica (mais simples): `auditoria (id, entidade, entidade_id,
  acao, usuario_id, dados_antes, dados_depois, motivo, criado_em)`.
- Event-sourcing (mais robusto mas mais complexo): eventos como fonte
  de verdade, estado atual é derivado.

**D9. Deploy: dev sozinho ou com colaborador?**
Muda estratégia de branch/review/CI.

**D10. Migração de dados do V1: batch único ou coexistência?**
Estratégia detalhada na Parte 8.

---

## Parte 8 — Estratégia de migração e coexistência

### 8.1 Coexistência recomendada

**Não fazer cutover ("desliga V1, liga V2").** Isso é receita pra
problema em sistema que os colegas usam todo dia.

**Fazer parallel run:**

1. V1 continua rodando em produção com todos os módulos atuais.
2. V2 começa focado no **primeiro módulo novo** (recomendação:
   **Contratos**, porque ainda não existe no V1 — zero conflito).
3. Contratos no V2 lê dados de Licitação do V1 (via banco compartilhado
   ou API/import periódico).
4. Servidores começam a usar V2 só para Contratos. O resto continua no V1.
5. **Módulo por módulo**, o V2 vai absorvendo os outros. Aplic completo
   depois de Contratos, Relatórios personalizados em seguida.
6. Quando todos os módulos estão no V2 (ou o suficiente para o dia a
   dia), aposenta o V1.

**Prazo realista:** 12 a 18 meses de parallel run.

### 8.2 Migração de dados

- **Dados históricos** (demandas, cotações, licitações fechadas) — migrar
  em batch único no início. Snapshot da pesquisa de preço é gerado no
  momento da migração (usando o motor do V1 uma última vez).
- **Servidores e empresas** — migrar em batch.
- **Parâmetros e setores demandantes** — migrar em batch.
- **Dados vivos** (demandas em andamento no V1 no momento da migração)
  — decidir caso a caso: manter no V1 até fechar, ou "portar" cada uma
  manualmente para o V2.

### 8.3 O que fazer com o V1 depois do V2 estabilizar

- Manter online por 12 meses só para consulta (read-only).
- Depois arquiva (dump do banco + snapshot do código) e desliga.

---

## Parte 9 — Como iniciar o novo chat

### 9.1 Prompt sugerido para colar no novo chat

Cole o texto abaixo (adaptando o que quiser) para começar bem:

---

> Preciso de ajuda para começar o projeto **MT Par 2.0** — reescrita do
> zero do meu sistema atual de gestão de licitações, com stack moderna.
>
> Vou anexar 3 documentos preparados por um Claude anterior que fez
> auditoria completa do sistema atual (V1):
>
> 1. **BRIEFING_MT_PAR_2.md** — briefing consolidado com contexto de
>    negócio, resumo do V1, domínio, regras críticas, lições aprendidas
>    e visão do V2. **Comece por este documento.**
> 2. **AUDITORIA.md** — auditoria completa do V1, com casos de uso
>    formais, requisitos funcionais/não funcionais e regras de negócio.
>    Use como referência do que o V1 faz hoje.
> 3. **PESQUISA_PRECO_REFATORAMENTO.md** — análise profunda do módulo
>    de pesquisa de preço, que é o mais crítico e onde já tive prejuízo
>    financeiro real. O V2 tem que nascer com essa arquitetura correta.
>
> Contexto pessoal: sou usuário do sistema, não desenvolvedor
> profissional. Já uso Claude Code no VS Code para mexer no V1. Preciso
> que você me guie passo a passo na construção do V2.
>
> **Primeiro passo que eu quero:** você lê os três documentos, e me faz
> **as perguntas de arquitetura que ainda estão em aberto** (estão na
> Parte 7 do BRIEFING). Não comece a codar até a gente ter alinhado
> essas decisões. Vamos amadurecer o plano antes de programar.
>
> Direção que já decidi:
> - Stack: **Laravel** (PHP moderno, sem framework foi o problema no V1).
> - Frontend inicial: **Blade + Alpine + Tailwind** (simples, rápido).
> - Vou continuar rodando localmente no meu setor por enquanto (mesma
>   máquina do V1, ou uma nova a definir).
> - **Não vou fazer cutover** — V2 nasce paralelo ao V1, migrando módulo
>   por módulo. Primeiro módulo do V2 será **Contratos** (que ainda não
>   existe no V1, então zero conflito).

---

### 9.2 O que anexar ao prompt

Além do prompt acima, anexe:

1. `docs/BRIEFING_MT_PAR_2.md` (este documento)
2. `docs/AUDITORIA.md`
3. `docs/PESQUISA_PRECO_REFATORAMENTO.md`
4. `database/schema.sql`

Opcionalmente (se o chat suportar):

- Alguns arquivos-chave do V1 para o novo Claude ver como o V1 realmente
  é implementado hoje. Sugestões:
  - `app/models/AnalisePrecos.php` — motor de análise 70/30
  - `app/models/Cotacao.php` — modelo central
  - `app/models/Licitacao.php` — modelo com muitos estados
  - `app/controllers/PropostaVencedoraController.php` — controller mais
    complexo
  - `app/views/proposta_vencedora.php` — view mais complexa (com JS
    embutido problemático)

### 9.3 Recomendações para conduzir a nova conversa

- **Comece pela arquitetura, não pelo código.** Peça pro novo Claude
  desenhar módulos, entidades e fluxos antes de gerar arquivo.
- **Uma decisão por vez.** Não tente decidir tudo da Parte 7 ao mesmo
  tempo. Comece pelo banco, depois frontend, depois deploy.
- **Faça o novo Claude testar cada decisão contra as lições L1-L10 da
  Parte 5.** "Como o V2 evita repetir L1 nesse desenho?"
- **Peça artefatos concretos por marco:** ADR (Architecture Decision
  Record) para cada decisão grande, README do projeto, primeira
  migration, primeiro Service, primeiro teste.
- **Evite pedir "código completo do sistema" de uma vez.** Vá por
  módulos, começando pelo Contratos.
- **Quando o novo Claude sugerir algo que parece contradizer o V1,
  compare aqui.** Traga a resposta pra este chat pra gente conferir se
  faz sentido antes de aceitar. Você conhece o negócio melhor que
  qualquer um.

---

*Fim do briefing. Boa jornada com o V2.*
