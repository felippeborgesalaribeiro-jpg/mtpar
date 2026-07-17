
CREATE TABLE IF NOT EXISTS servidores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    matricula TEXT NOT NULL DEFAULT '',
    cargo TEXT NOT NULL DEFAULT '',
    usuario TEXT NOT NULL DEFAULT '',
    senha_hash TEXT NOT NULL DEFAULT '',
    nivel_acesso TEXT NOT NULL DEFAULT 'COMUM',
    criado_em TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS parametros (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    preco_publico INTEGER NOT NULL DEFAULT 0,
    criado_em TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS demandas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_processo TEXT NOT NULL,
    link_sigadoc TEXT NOT NULL DEFAULT '',
    setor_demandante TEXT NOT NULL DEFAULT '',
    data_recebimento TEXT NOT NULL,
    objeto TEXT NOT NULL DEFAULT '',
    servidor_responsavel_id INTEGER,
    status TEXT NOT NULL DEFAULT 'EM ANDAMENTO',
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    deleted_at TEXT DEFAULT NULL,
    FOREIGN KEY (servidor_responsavel_id) REFERENCES servidores(id)
);

CREATE TABLE IF NOT EXISTS empresas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    nome_fantasia TEXT NOT NULL DEFAULT '',
    cnpj TEXT NOT NULL UNIQUE,
    criado_em TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS licitacoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    demanda_id INTEGER NOT NULL,
    numero_processo TEXT NOT NULL,
    link_sigadoc TEXT NOT NULL DEFAULT '',
    setor_demandante TEXT NOT NULL DEFAULT '',
    data_recebimento TEXT NOT NULL,
    objeto TEXT NOT NULL DEFAULT '',
    servidor_responsavel_id INTEGER,
    edital_licitacao TEXT NOT NULL DEFAULT '',
    realizacao_sessao_publica TEXT,
    valor_estimado REAL,
    valor_adjudicado REAL,
    encaminhado_pactuacao_contrato TEXT,
    empresa_vencedora_id INTEGER,
    observacoes_proposta_vencedora TEXT NOT NULL DEFAULT '',
    data_adjudicacao_homologacao TEXT,
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (demanda_id) REFERENCES demandas(id) ON DELETE CASCADE,
    FOREIGN KEY (servidor_responsavel_id) REFERENCES servidores(id),
    FOREIGN KEY (empresa_vencedora_id) REFERENCES empresas(id)
);

CREATE TABLE IF NOT EXISTS itens_proposta_vencedora (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    licitacao_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    valor_proposto REAL NOT NULL,
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES itens(id) ON DELETE CASCADE,
    UNIQUE (licitacao_id, item_id)
);

CREATE TABLE IF NOT EXISTS cotacoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_processo TEXT NOT NULL,
    orgao_setor TEXT NOT NULL DEFAULT '',
    procedimento TEXT NOT NULL DEFAULT '',
    tipo_julgamento TEXT NOT NULL DEFAULT '',
    objeto TEXT NOT NULL DEFAULT '',
    servidor_id INTEGER NOT NULL,
    criterio_consolidacao TEXT NOT NULL DEFAULT 'MEDIANA',
    status TEXT NOT NULL DEFAULT 'EM_ANDAMENTO',
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    demanda_id INTEGER REFERENCES demandas(id),
    deleted_at TEXT DEFAULT NULL,
    FOREIGN KEY (servidor_id) REFERENCES servidores(id)
);

CREATE TABLE IF NOT EXISTS lotes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cotacao_id INTEGER NOT NULL,
    numero TEXT NOT NULL,
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (cotacao_id) REFERENCES cotacoes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lotes_proposta_vencedora (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    licitacao_id INTEGER NOT NULL,
    lote_id INTEGER NOT NULL,
    empresa_vencedora_id INTEGER NOT NULL,
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (licitacao_id) REFERENCES licitacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (lote_id) REFERENCES lotes(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_vencedora_id) REFERENCES empresas(id),
    UNIQUE (licitacao_id, lote_id)
);

CREATE TABLE IF NOT EXISTS itens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lote_id INTEGER NOT NULL,
    numero INTEGER NOT NULL,
    descricao TEXT NOT NULL DEFAULT '',
    unidade TEXT NOT NULL DEFAULT 'UN',
    quantidade REAL NOT NULL DEFAULT 1,
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (lote_id) REFERENCES lotes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS precos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    parametro TEXT NOT NULL DEFAULT '',
    valor REAL NOT NULL,
    fonte TEXT NOT NULL DEFAULT '',
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (item_id) REFERENCES itens(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS processos_vantajosidade (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_ata TEXT NOT NULL,
    orgao_gerenciador TEXT NOT NULL DEFAULT '',
    objeto TEXT NOT NULL DEFAULT '',
    servidor_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'EM_ANDAMENTO',
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    demanda_id INTEGER REFERENCES demandas(id),
    deleted_at TEXT DEFAULT NULL,
    FOREIGN KEY (servidor_id) REFERENCES servidores(id)
);

CREATE TABLE IF NOT EXISTS itens_vantajosidade (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    processo_id INTEGER NOT NULL,
    lote TEXT NOT NULL,
    item TEXT NOT NULL,
    descricao TEXT NOT NULL DEFAULT '',
    unidade TEXT NOT NULL DEFAULT 'UN',
    quantidade REAL NOT NULL DEFAULT 1,
    preco_ata REAL NOT NULL,
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (processo_id) REFERENCES processos_vantajosidade(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS precos_vantajosidade (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    parametro TEXT NOT NULL DEFAULT '',
    valor REAL NOT NULL,
    fonte TEXT NOT NULL DEFAULT '',
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (item_id) REFERENCES itens_vantajosidade(id) ON DELETE CASCADE
);
