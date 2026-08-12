-- Destinatarios: mapeia token opaco -> pessoa/template
CREATE TABLE IF NOT EXISTS recipients (
  token    TEXT PRIMARY KEY,
  email    TEXT NOT NULL,
  name     TEXT,
  template TEXT,
  created  TEXT DEFAULT (datetime('now'))
);

-- Cliques registrados server-side (fonte da verdade de "quem clicou")
CREATE TABLE IF NOT EXISTS clicks (
  id       INTEGER PRIMARY KEY AUTOINCREMENT,
  token    TEXT,
  email    TEXT,
  name     TEXT,
  template TEXT,
  ts       TEXT,
  ip       TEXT,
  ua       TEXT,
  country  TEXT
);

CREATE INDEX IF NOT EXISTS idx_clicks_token ON clicks(token);
