# Refatoramento da Pesquisa de Preço — Diagnóstico e Desenho

**Data:** 2026-09-23
**Commit auditado:** `2d14dfd` (branch `main`)
**Escopo:** Só o módulo de Pesquisa de Preço (Cotação + Mapa 70/30 + tudo que consome esses valores). Não trata de Licitação, Vantajosidade ou Contratos como núcleo (só onde eles LEEM os valores da Cotação).

Este documento é a análise técnica pedida para corrigir uma decisão de
arquitetura errada. Não é para ser executado agora — é para a gente ter
o alinhamento antes de mexer em código.

---

## Sumário

- [Parte 1 — Diagnóstico preciso do que existe hoje](#parte-1--diagnóstico-preciso-do-que-existe-hoje)
    - 1.1 [Como a análise 70/30 é feita](#11-como-a-análise-7030-é-feita)
    - 1.2 [Todos os pontos que RECALCULAM a análise da mesma cotação](#12-todos-os-pontos-que-recalculam-a-análise-da-mesma-cotação)
    - 1.3 [Cadeia de propagação — o que acontece se um dos pontos divergir](#13-cadeia-de-propagação--o-que-acontece-se-um-dos-pontos-divergir)
    - 1.4 [Erros na auditoria anterior que precisam ser corrigidos](#14-erros-na-auditoria-anterior-que-precisam-ser-corrigidos)
    - 1.5 [Riscos concretos de dinheiro](#15-riscos-concretos-de-dinheiro)
- [Parte 2 — Desenho da nova arquitetura](#parte-2--desenho-da-nova-arquitetura)
    - 2.1 [Princípio: uma única fonte de verdade](#21-princípio-uma-única-fonte-de-verdade)
    - 2.2 [Modelo de dados novo (snapshot)](#22-modelo-de-dados-novo-snapshot)
    - 2.3 [Fluxo de escrita — quando o snapshot nasce e é atualizado](#23-fluxo-de-escrita--quando-o-snapshot-nasce-e-é-atualizado)
    - 2.4 [Fluxo de leitura — quem passa a ler do snapshot](#24-fluxo-de-leitura--quem-passa-a-ler-do-snapshot)
    - 2.5 [Como cada consumidor muda](#25-como-cada-consumidor-muda)
    - 2.6 [Regras de invalidação e reabertura](#26-regras-de-invalidação-e-reabertura)
    - 2.7 [Compatibilidade com cotações históricas](#27-compatibilidade-com-cotações-históricas)
    - 2.8 [Extensão para Vantajosidade](#28-extensão-para-vantajosidade)
- [Parte 3 — Plano de implementação por fases](#parte-3--plano-de-implementação-por-fases)
- [Parte 4 — Perguntas que preciso decidir com você antes de codar](#parte-4--perguntas-que-preciso-decidir-com-você-antes-de-codar)

---

## Parte 1 — Diagnóstico preciso do que existe hoje

### 1.1 Como a análise 70/30 é feita

O motor da análise é a classe `AnalisePrecos` (`app/models/AnalisePrecos.php`).
Ela recebe:

- Uma lista de preços de um item (cada preço com `valor` e `parametro`).
- O critério de consolidação (`MEDIA`, `MEDIANA`, `MENOR_PRECO`,
  `PLANILHA_ORCAMENTARIA`).
- A lista de parâmetros que são "preço público" (isenção de inexequibilidade).
- Uma flag `arredondarValorReferencia` (true por padrão; false só para
  cotações criadas antes de 2026-08-19).

E devolve o resultado da análise:

```
[
  'etapa1' => [
    // por preço: media_demais, diferenca, resultado (EXCESSIVO | APROVADO)
  ],
  'etapa2' => [
    // por preço: media_demais, comparacao, resultado (INEXEQUÍVEL | EXCEÇÃO | APROVADO)
  ],
  'resultado_final' => [
    // por preço: classificação final consolidada
  ],
  'valor_referencia' => 1234.56  // número final que vira o "preço unitário estimado"
]
```

O motor em si é bem escrito, correto e testado. **O problema não está aqui.**

O problema está em **quem chama esse motor** e **quantas vezes**.

### 1.2 Todos os pontos que RECALCULAM a análise da mesma cotação

Rastreei cada `Item::analisar()` e cada `Cotacao::calcularValorTotal()`
no código. Estes são os **7 pontos** onde a análise 70/30 é refeita para
a MESMA cotação, cada um sem saber do outro:

| # | Arquivo | Linha | Contexto | O que consome do resultado |
|---|---|---|---|---|
| **P1** | `app/controllers/MapaController.php` | 76 | Tela do Mapa Comparativo E tela de Validação do Preço de Referência | `resultado_final` (pra pintar aprovados/reprovados), `valor_referencia`, subtotais |
| **P2** | `app/models/Cotacao.php` | 232 | `calcularValorTotal()` — método chamado pela Licitação para popular `valorEstimado` | Só `valor_referencia` (multiplica por quantidade e soma) |
| **P3** | `app/views/cotacao.php` | 189 | Tela da Cotação — para cada item, mostra o resultado do 70/30 e o "valor de referência (...)" no cabeçalho | `resultado_final`, `valor_referencia` |
| **P4** | `app/views/proposta_vencedora.php` | 243 | Tela de Conferir Proposta — para setar `data-ref` (usado pelo JS) e mostrar coluna "Ref. unit." | Só `valor_referencia` |
| **P5** | JS embutido em `proposta_vencedora.php` | ~407 | Recalcula subtotais em tempo real conforme digita a proposta | `data-ref` populado pelo P4 |
| **P6** | `app/models/GeradorAnaliseCritica.php` | 249 e 293 (por item) | Word "Análise Crítica" — monta a tabela de cálculo, os textos de justificativa por preço, o valor unitário estimado e o subtotal do lote | `etapa1`, `etapa2`, `resultado_final`, `valor_referencia` — usa **tudo** |
| **P7** | `app/models/GeradorComparacaoProposta.php` | 154 | Word "Comparação de Proposta vs Referência" — mostra coluna "Ref. unitário" e situação da proposta | Só `valor_referencia` |

**Como este arquivo mostra em 1.4, `GeradorTermoAdjudicacaoHomologacao`
NÃO consome `valor_referencia`.** Ele opera puramente sobre o valor
proposto pelo licitante vencedor. Isso está certo — o Termo é sobre
o preço homologado, não sobre a referência.

Além disso, `Cotacao::calcularValorTotal` (P2) é chamada por:

- `Licitacao::criarApartirDeDemanda` (linha 96) — quando a Demanda é
  concluída e vira Licitação, grava o valor estimado.
- `Licitacao::fromArray` no caminho `sincronizar: true` (linha 235) —
  **cada vez que a Licitação é carregada por `buscarPorId`** ela recalcula
  o valor estimado da cotação e regrava se diferir por mais de R$ 0,001.
  Isso é **outra armadilha silenciosa** de recalculo: qualquer mudança
  no motor de análise entre duas aberturas da licitação faz o valor
  estimado dela pular sozinho.

### 1.3 Cadeia de propagação — o que acontece se um dos pontos divergir

Este é o problema real. **Se qualquer um dos 7 pontos calcular diferente**
(porque alguém esqueceu de passar a flag `arredondar`, ou passou um
critério diferente, ou tem versão do código antiga em cache do PHP), o
sistema imediatamente contradiz a si mesmo:

```
Servidor abre o Mapa (P1) e valida            → R$ X
Servidor abre a Cotação (P3) para reconferir  → pode ser R$ X ou R$ Y
Servidor baixa a Análise Crítica (P6)         → pode ser R$ X ou R$ Y
Conferência de Proposta (P4/P5) mostra        → pode ser R$ X ou R$ Y
Licitação herda valor estimado (P2)           → pode ser R$ X ou R$ Y
Baixa Comparação de Proposta (P7)             → pode ser R$ X ou R$ Y
Termo de Adjudicação (usa proposta, não ref)  → OK, imune
```

Foi exatamente isso que causou seu prejuízo. Cada correção que fizemos
até agora ("passa a flag em P6", "usa round2 em P5", "arredonda antes de
somar em P1 e P2") **funcionou pontualmente**, mas manteve o desenho
errado: cada consumidor tem sua própria autonomia para redecidir a
matemática.

### 1.4 Erros na auditoria anterior que precisam ser corrigidos

Verifiquei fisicamente o repositório (`ls app/models/Gerador*.php`):

| Erro na auditoria de ontem | Verdade |
|---|---|
| Documentei a classe `GeradorRelatorioPesquisa` como uma dos 5 geradores | **Não existe.** O arquivo foi removido em algum momento e ficou só a referência morta. |
| Rota `case 'gerar_relatorio'` listada como funcional em `index.php:188` | **É código morto** que instancia `GeradorAnaliseCritica`, mas nenhuma view aponta pra essa rota. Só `relatorio_formulario` (que também instancia `GeradorAnaliseCritica` via `case 'gerar_relatorio'`) está em uso. |
| Diagrama de classes com `GeradorRelatorioPesquisa --> PhpWord` | **Fantasma.** |
| Item D04 da seção 11.5 ("análise 70/30 duplicada em AnalisePrecos e AnaliseVantajosidade") | Está CERTO, mas foi classificado como "prioridade baixa". Deveria ter sido classificado como **defeito arquitetural crítico**, junto com o item D01 ("Geradores com constantes duplicadas") — na verdade o problema real dos Geradores não é a duplicação de fonte/tamanho, é **cada um recalcular sozinho o que já foi calculado no Mapa**. |

Esses três erros precisam ser corrigidos em `docs/AUDITORIA.md` na
próxima rodada de manutenção do documento.

### 1.5 Riscos concretos de dinheiro

Cenários reais que o desenho atual permite (todos já ocorreram ou podem
ocorrer):

**R1. Divergência silenciosa entre Mapa e Word.** Servidor valida no
Mapa, imprime a Análise Crítica, assina, envia. Meses depois, alguém
edita um preço da cotação achando que era uma correção pequena. Ao
regerar a Análise Crítica (ou ao qualquer usuário reabrir o Mapa), os
valores mudam. O Word já assinado agora contradiz o sistema. **Impossível
provar qual era a verdade oficial.**

**R2. Licitação com valor estimado que "escorrega".** Toda vez que
`Licitacao::buscarPorId` é chamada, ela recalcula `calcularValorTotal`
e regrava se diferir. Se qualquer mudança no motor de análise (uma nova
data de corte, um novo critério padrão, um bug corrigido) mudar o
resultado, **o valor estimado da licitação muda sozinho sem ninguém
autorizar**. O usuário abre a tela e vê um número diferente do que
estava lá ontem.

**R3. Preço público adicionado depois da cotação fechar.** A tabela
`parametros.preco_publico` é usada em tempo real por todos os pontos.
Se o admin marcar um novo parâmetro como "preço público" hoje, cotações
finalizadas de meses atrás **passam a classificar preços daquele
parâmetro como aprovado (exceção)** — porque cada consumidor consulta
a tabela de parâmetros no momento do cálculo, não no momento do
fechamento. O valor de referência de cotações antigas muda
retroativamente sem qualquer aviso.

**R4. Bugs de arredondamento futuros.** Cada nova correção precisa ser
propagada nos 7 pontos. Se esquecermos um, ele fica desatualizado. O
número de pontos vai crescer (Contratos vai virar P8, algum relatório
personalizado será P9, etc.). É insustentável.

---

## Parte 2 — Desenho da nova arquitetura

### 2.1 Princípio: uma única fonte de verdade

**Regra número um:** o valor de referência de um item é decidido **uma
única vez**, no momento em que a cotação é finalizada. A partir dali:

- **É gravado** em uma tabela dedicada (o "snapshot" da cotação).
- **Nenhum outro lugar** do sistema recalcula — todos leem o snapshot.
- **Se precisar mudar**, é uma ação explícita e auditada ("reabrir
  cotação"), que gera nova versão do snapshot e deixa rastro.

**Regra número dois:** o motor `AnalisePrecos` continua onde está, com
zero mudança de lógica. O que muda é **onde os resultados param**: ao
invés de serem recalculados sob demanda, ficam gravados.

**Regra número três:** os textos de justificativa da Análise Crítica
(que hoje são gerados a partir do resultado da análise em tempo real)
passam a ser gerados uma única vez também, junto com o snapshot, e
gravados. Assim o docx reproduz palavra por palavra o que foi decidido
no fechamento.

### 2.2 Modelo de dados novo (snapshot)

Uma tabela nova por nível (item, lote, cotação):

```sql
-- Registro fotografado de cada item no momento do fechamento.
CREATE TABLE cotacao_snapshot_item (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    snapshot_id INTEGER NOT NULL,      -- FK para cotacao_snapshot
    item_id INTEGER NOT NULL,          -- FK para itens (só referência, não lógica)
    lote_numero TEXT NOT NULL,         -- número do lote naquele momento
    item_numero INTEGER NOT NULL,
    descricao TEXT NOT NULL,
    unidade TEXT NOT NULL,
    quantidade REAL NOT NULL,
    valor_referencia REAL NOT NULL,    -- SEMPRE 2 casas decimais (centavos)
    valor_total_item REAL NOT NULL,    -- valor_referencia * quantidade, JÁ ARREDONDADO
    criterio_consolidacao TEXT NOT NULL,  -- MEDIA, MEDIANA, etc.
    UNIQUE (snapshot_id, item_id)
);

-- Cópia dos preços com sua classificação final no 70/30.
CREATE TABLE cotacao_snapshot_preco (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    snapshot_item_id INTEGER NOT NULL, -- FK para cotacao_snapshot_item
    preco_id INTEGER NOT NULL,         -- FK para precos (referência)
    valor REAL NOT NULL,
    parametro TEXT NOT NULL,           -- copiado (o nome do parâmetro pode mudar depois)
    parametro_eh_preco_publico INTEGER NOT NULL DEFAULT 0,  -- estado NO MOMENTO
    fonte TEXT NOT NULL,
    -- Resultado da análise 70/30, congelado:
    etapa1_media_demais REAL,
    etapa1_diferenca REAL,             -- percentual (0.30 = 30% acima)
    etapa1_resultado TEXT,             -- EXCESSIVO | APROVADO
    etapa2_media_demais REAL,
    etapa2_comparacao REAL,            -- razão preço/media (0.70 = 70%)
    etapa2_resultado TEXT,             -- INEXEQUIVEL | APROVADO | EXCECAO_PRECO_PUBLICO
    resultado_final TEXT NOT NULL,     -- consolidação: EXCESSIVO | INEXEQUIVEL | APROVADO | EXCECAO
    justificativa_texto TEXT NOT NULL, -- texto pronto pra Word, congelado
    ordem INTEGER NOT NULL             -- ordem de exibição (mesma do cadastro)
);

-- Cabeçalho do snapshot da cotação (uma linha por versão).
CREATE TABLE cotacao_snapshot (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cotacao_id INTEGER NOT NULL,       -- FK para cotacoes
    versao INTEGER NOT NULL,           -- 1, 2, 3... incrementa a cada reabertura
    valor_total_cotacao REAL NOT NULL, -- soma dos valor_total_item (2 casas)
    servidor_id INTEGER NOT NULL,      -- quem fechou/reabriu
    motivo_reabertura TEXT,            -- só na v2+; obrigatório se versao > 1
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    ativo INTEGER NOT NULL DEFAULT 1,  -- só o snapshot ativo é lido; os antigos ficam pra auditoria
    UNIQUE (cotacao_id, versao)
);
```

**Por que separar em 3 tabelas:** cada nível de granularidade é
consumido por telas diferentes (Mapa lê tudo, Comparação de Proposta lê
só o cabeçalho + itens sem preços, Licitação lê só o cabeçalho). Também
permite indexar melhor. E deixa `cotacao_snapshot.ativo` como o único
ponto de "qual versão vale hoje".

**Nota sobre o texto de justificativa:** hoje ele é gerado dentro do
`GeradorAnaliseCritica` (método `montarJustificativaPreco`) a partir dos
números da análise. Passa a ser gerado no momento do fechamento e
gravado em `justificativa_texto`. Assim o Word simplesmente lê e imprime.

### 2.3 Fluxo de escrita — quando o snapshot nasce e é atualizado

Só existem **três** momentos em que o snapshot é criado ou atualizado:

**M1. No fechamento da cotação (`CotacaoController::finalizar`).**
Depois de validar que tem mínimo de 3 preços aprovados por item, o
sistema:

1. Chama `AnalisePrecos::calcular()` **uma última vez** para cada item.
2. Gera `cotacao_snapshot` versão 1.
3. Grava `cotacao_snapshot_item` e `cotacao_snapshot_preco` para cada
   item e preço.
4. Gera e grava o `justificativa_texto` de cada preço.
5. Marca `cotacoes.status = 'FINALIZADA'`.

Todo o cálculo passa a ser **passado**. Não existe mais recalcular.

**M2. No fechamento de uma cotação de republicação de lote.** Mesma
coisa que M1, disparado quando a cotação-filha (com
`eh_republicacao_lote = 1`) é finalizada.

**M3. Na reabertura autorizada de uma cotação já finalizada.** Novo
fluxo:

1. Admin (só admin) abre a cotação e clica "Reabrir para correção".
2. Modal exige motivo textual da reabertura.
3. Cotação volta pra `EM_ANDAMENTO`; snapshot atual não é apagado, só
   marcado `ativo = 0`.
4. Servidor edita o que precisa (adicionar/remover preço, mudar
   critério, corrigir quantidade).
5. Finaliza de novo → gera snapshot versão 2, marca versão 1 como
   histórico, marca 2 como ativa.
6. O motivo da reabertura é preenchido no cabeçalho da versão nova.

**Consequência importante:** o docx da Análise Crítica gerado antes da
reabertura continua correspondendo à versão 1 (que ainda está no banco
como histórico). Ou seja, o documento assinado nunca mais fica
desalinhado do que estava no sistema quando foi gerado.

### 2.4 Fluxo de leitura — quem passa a ler do snapshot

**Todos os 7 pontos** listados em 1.2 param de chamar `Item::analisar()`
e passam a ler do snapshot ativo da cotação. O motor `AnalisePrecos`
fica **inacessível fora do fluxo de fechamento** — só o
`CotacaoController::finalizar` (e a rotina de reabertura) pode chamá-lo.

Isso vira uma regra técnica reforçável: adicionar uma verificação no
código (ou mais simples, mover `AnalisePrecos` pra namespace
`Cotacao/Interno/`) para deixar claro que ninguém deve usar diretamente.

### 2.5 Como cada consumidor muda

| Consumidor atual | Como fica no novo desenho |
|---|---|
| **P1** `MapaController::montarMapaLotes` | Lê `cotacao_snapshot_item` e `cotacao_snapshot_preco` da cotação. Zero recálculo. |
| **P2** `Cotacao::calcularValorTotal` | Vira `Cotacao::valorTotalDoSnapshot(): float` — leitura direta do `cotacao_snapshot.valor_total_cotacao` da versão ativa. |
| **P3** `views/cotacao.php` (loop de itens no card) | Para cotação **finalizada**, lê do snapshot. Para cotação **em andamento**, chama `AnalisePrecos` em memória (sem gravar) só para visualizar — deixa claro na tela "Prévia (não oficial até fechar)". |
| **P4** `views/proposta_vencedora.php` — coluna Ref. unit. e `data-ref` | Lê `cotacao_snapshot_item.valor_referencia` direto. |
| **P5** JS embutido | Mantém `round2()` como está — continua fazendo `qtd × ref` com ref já arredondado, e agora ref vem do snapshot. |
| **P6** `GeradorAnaliseCritica` | Passa a ser um **transcritor**: lê snapshot e escreve. Zero decisão de análise. Zero recálculo. Justificativa lida do campo `justificativa_texto`. |
| **P7** `GeradorComparacaoProposta` | Lê valor_referencia do snapshot. Zero recálculo. Compara com `itens_proposta_vencedora` (que já é o que ele faz). |
| **Extra** `Licitacao::valorEstimado` | Vira leitura direta do snapshot da cotação vinculada. Some o "self-sync" perigoso do `buscarPorId`. |

**Nenhum consumidor precisa saber da flag `deveArredondarValorReferencia`
mais.** Ela era um workaround para não retroalterar cotações antigas —
com snapshot, retroalteração é **impossível por design** (para alterar,
tem que reabrir explicitamente).

### 2.6 Regras de invalidação e reabertura

**O que acontece se alguém tentar editar um preço de cotação já
finalizada?**

Hoje: nada trava, mas as consequências são invisíveis (o valor de
referência muda silenciosamente na próxima leitura).

Novo desenho:

- Edição de preço, item ou lote em cotação `FINALIZADA` fica **bloqueada
  na UI** (formulários somem, botões viram "Ver" em vez de "Editar").
- Para editar, precisa reabrir a cotação. Reabrir só admin, com motivo.
- Ao reabrir, o snapshot ativo é preservado (marcado `ativo = 0`,
  virando histórico) até o novo fechamento.
- Se a reabertura for cancelada (fecho sem alterar), pode simplesmente
  re-marcar o snapshot antigo como ativo em vez de gerar versão nova
  igual à anterior.

**Cotação vinculada a Licitação já homologada:** reabertura fica proibida
por padrão. Se o admin realmente precisar (caso extremo de erro
detectado após homologação), tem que reabrir a Licitação primeiro (que
também vira ação auditada com motivo). Esse fluxo já existe implícito
no sistema — a gente só formaliza.

### 2.7 Compatibilidade com cotações históricas

Existe um problema real: hoje há cotações em produção que **nunca
tiveram um snapshot** porque o mecanismo não existia. A migração precisa
lidar com isso.

**Estratégia de retroatividade — 3 passos:**

**Passo 1 — Backfill.** Rodar uma vez, no deploy, um script que:

- Para cada cotação **finalizada** existente, chama `AnalisePrecos` com
  a flag `arredondar` correta (a mesma que o sistema usa hoje via
  `deveArredondarValorReferencia`).
- Grava o snapshot versão 1 dessas cotações.
- O resultado do backfill DEVE ser exatamente o que a cotação exibe
  hoje — se der diferente, é sinal de um bug no motor que precisa ser
  investigado antes de fechar o snapshot.

**Passo 2 — Congelamento imediato.** Depois do backfill, o motor
`AnalisePrecos` fica indisponível fora do fluxo de finalização. As
leituras já são do snapshot. Ninguém mais consegue "resincronizar"
sozinho.

**Passo 3 — Aposentar a flag `deveArredondarValorReferencia`.** Como
o snapshot já congelou o valor que era exibido antes do refactoring,
não faz mais diferença se cotações antigas usaram arredondamento ou
não — o número foi fotografado, ponto. A flag e a constante
`DATA_CORTE_VALOR_REFERENCIA_ARREDONDADO` podem ser removidas.

**Cotações em andamento (não finalizadas):** ficam sem snapshot até
serem fechadas. Isso é OK — enquanto está em andamento, os valores são
prévia (Prova A não é oficial). A UI passa a deixar isso claro.

### 2.8 Extensão para Vantajosidade

O módulo de Vantajosidade (`ProcessoVantajosidade` + `ItemVantajosidade`
+ `AnaliseVantajosidade`) tem exatamente o mesmo problema. Cada
`gerarAnaliseCritica`, cada `mostrar` na view chama `analisar()` de novo.

O mesmo desenho se aplica: quando o processo de vantajosidade é
finalizado, gera snapshot próprio. Todos os consumidores leem dali.

Fica pra uma segunda fase. Prioridade é Cotação primeiro (é onde o
prejuízo aconteceu).

---

## Parte 3 — Plano de implementação por fases

Cada fase é um marco entregável, testável, deployable de forma
independente. **Não é para fazer tudo de uma vez.**

**Fase 0 — Correção da auditoria (30 min).** Corrigir a
`AUDITORIA.md` para remover o Gerador fantasma e reclassificar o
defeito arquitetural. Zero risco.

**Fase 1 — Schema do snapshot (1 dia).** Migration que cria as três
tabelas novas, vazias. Zero uso ainda. Só código de infraestrutura.
Zero risco.

**Fase 2 — Serviço de gravação do snapshot (2 dias).** Uma classe
`FecharCotacao` (ou similar) que sabe pegar uma cotação, rodar
`AnalisePrecos`, e gravar tudo nas três tabelas. Testes unitários
comparando com o resultado do motor atual — precisa dar EXATAMENTE o
mesmo número.

**Fase 3 — Backfill piloto (1 dia).** Script separado que roda em uma
única cotação de teste, grava o snapshot dela, compara com o Mapa
atual visualmente. Não substitui nada ainda. É só validação.

**Fase 4 — Backfill em massa + trocar leitura do Mapa (2 dias).**
Rodar backfill em todas as cotações finalizadas. Adaptar
`MapaController` para tentar ler do snapshot; se não existir, cai no
recálculo atual (fallback de segurança durante a transição).

**Fase 5 — Trocar leitura da Licitação (1 dia).** Adaptar
`Cotacao::calcularValorTotal` para retornar do snapshot. Adaptar
`Licitacao::criarApartirDeDemanda` e remover o self-sync perigoso.

**Fase 6 — Trocar Word de Análise Crítica (2 dias).** Adaptar
`GeradorAnaliseCritica` para virar transcritor. Gerar
`justificativa_texto` no fechamento (Fase 2) e ler aqui.

**Fase 7 — Trocar Word de Comparação de Proposta (1 dia).** Adaptar
`GeradorComparacaoProposta` para ler do snapshot.

**Fase 8 — Trocar view da Cotação e da Conferência de Proposta (2
dias).** Adaptar `views/cotacao.php` e `views/proposta_vencedora.php`
para ler do snapshot quando a cotação estiver finalizada; manter
recálculo em memória (marcado como "prévia") para cotações em
andamento.

**Fase 9 — Remover fallbacks e o motor exposto (1 dia).** Depois que
todos os consumidores estão lendo do snapshot com estabilidade em
produção (recomendo 2 semanas de uso), remover o fallback de recálculo.
Mover `AnalisePrecos` para escopo interno.

**Fase 10 — Reabertura de cotação (2 dias).** Implementar o fluxo de
reabertura com auditoria, admin-only, motivo obrigatório. Bloqueio de
edição em cotação finalizada.

**Fase 11 — Aposentar a flag arredondar (0,5 dia).** Remover
`deveArredondarValorReferencia` e a constante de data de corte. Depois
que tudo lê do snapshot, ela é dead code.

**Total estimado:** ~15-17 dias de trabalho real, distribuídos ao longo
de 4-6 semanas de calendário (dando espaço para testar cada fase em
produção antes de seguir). Pode ir mais rápido se der prioridade — mas
recomendo o passo a passo pra não repetir o erro de "corrigir tudo de
uma vez e quebrar coisa nova".

---

## Parte 4 — Perguntas que preciso decidir com você antes de codar

Não vou tocar em nada sem sua confirmação nestes pontos:

**Q1. Reabrir cotação finalizada — quem pode?**
- Só admin? Ou qualquer servidor?
- Precisa de justificativa por escrito (grava em campo)?
- Precisa de aprovação de dois pares (workflow) ou é ação individual?

**Q2. O que fazer com cotações finalizadas cuja Licitação já foi
homologada?**
- Bloqueia reabertura totalmente?
- Permite mas exige reabrir Licitação primeiro?
- Não faz distinção?

**Q3. Snapshot em cotações em andamento?**
- Nenhum snapshot até fechar? (mais simples, o que sugeri)
- Ou snapshot "rascunho" atualizado a cada mudança de preço? (mais
  robusto contra crash, mais complexo)

**Q4. Cache de PhpWord: o docx gerado é guardado em algum lugar?**
- Hoje é gerado, baixado e o arquivo temporário é apagado.
- Vale guardar cópia junto ao snapshot pra auditoria? (Aumenta armazenamento
  mas dá rastreabilidade total.)

**Q5. Migração dos snapshots de cotações antigas: quando fazer?**
- Antes de trocar qualquer leitura (mais seguro, o que sugeri).
- Junto com cada tela sendo migrada (mais gradual, mais risco).

**Q6. Vantajosidade entra no mesmo refactoring ou fica pra depois?**
- Sugestão: **depois**. Cotação primeiro, com estabilidade, aí
  Vantajosidade.

**Q7. E se der divergência no backfill (o snapshot novo diferente do
que o Mapa mostrava)?**
- Tratar como bug do motor e investigar antes de gravar?
- Aceitar como "essa cotação estava com bug antigo" e gravar o valor
  novo?
- Marcar essas cotações e revisar manualmente?

Cada resposta muda partes do desenho. Prefiro alinhar antes de codar
do que voltar depois e retrabalhar.

---

## Anexo — Onde `AnalisePrecos` é acessível hoje (para referência)

```
AnalisePrecos::calcular()
    └── Item::analisar()
        ├── app/controllers/MapaController.php:76
        ├── app/models/Cotacao.php:201  (itensComPrecosInsuficientes)
        ├── app/models/Cotacao.php:232  (calcularValorTotal)
        │     └── app/models/Licitacao.php:96   (criarApartirDeDemanda)
        │     └── app/models/Licitacao.php:235  (self-sync em buscarPorId)
        ├── app/models/GeradorAnaliseCritica.php:249  (por lote)
        ├── app/models/GeradorAnaliseCritica.php:293  (por item, para tabela)
        ├── app/models/GeradorComparacaoProposta.php:154
        ├── app/views/cotacao.php:189
        └── app/views/proposta_vencedora.php:243
```

Quando o refactoring estiver pronto, essa árvore some inteira, restando
apenas:

```
AnalisePrecos::calcular()
    └── CotacaoSnapshotService::gerar()
        ├── CotacaoController::finalizar
        └── CotacaoController::reabrirEFinalizarDeNovo

CotacaoSnapshotService::ler()     ← todos os outros lugares chamam aqui
```

Simetria simples. Escreve em 2 lugares controlados. Lê em quantos precisar,
sem risco.

---

*Fim do documento. Aguardando decisões das 7 perguntas da Parte 4 antes
de iniciar qualquer implementação.*
