
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
    FOREIGN KEY (servidor_id) REFERENCES servidores(id)
);

CREATE TABLE IF NOT EXISTS lotes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cotacao_id INTEGER NOT NULL,
    numero TEXT NOT NULL,
    criado_em TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (cotacao_id) REFERENCES cotacoes(id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS parametros (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    preco_publico INTEGER NOT NULL DEFAULT 0,
    criado_em TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS servidores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    matricula TEXT NOT NULL DEFAULT '',
    cargo TEXT NOT NULL DEFAULT '',
    criado_em TEXT NOT NULL DEFAULT (datetime('now'))
);