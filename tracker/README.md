# tracker — registro de cliques (Cloudflare Worker + D1)

Componente que **identifica quem clicou** na campanha, sem coletar nenhuma
credencial. O link de cada isca carrega um `{{TOKEN}}` unico por pessoa; ao ser
aberto, o Worker registra o clique (token, IP, User-Agent, pais, horario) no
banco D1 e entrega a `landing_treinamento.html` de conscientizacao.

O registro e **server-side** (no proprio Worker), mais confiavel do que um
beacon em JavaScript, que falha se o cliente bloqueia script.

## Arquivos

| Arquivo | Papel |
|---|---|
| `src/worker.js` | Worker: registra o clique e serve a landing; `/__report?key=` mostra o relatorio |
| `schema.sql` | Tabelas `recipients` (token→pessoa) e `clicks` |
| `seed.example.sql` | Exemplo de cadastro de destinatarios (use placeholders; dados reais fora do git) |
| `build.mjs` | Embute a `landing_treinamento.html` em `src/landing.js` |
| `wrangler.toml` | Config do Worker + D1 + Custom Domain (preencha com o SEU dominio/ID) |

## Deploy

```bash
# 1. cria o banco e cola o id em wrangler.toml
wrangler d1 create rf_tracker

# 2. cria as tabelas
wrangler d1 execute rf_tracker --remote --file schema.sql

# 3. cadastra os destinatarios (arquivo seu, fora do git)
wrangler d1 execute rf_tracker --remote --file seed.sql

# 4. define a chave do relatorio
echo -n "UMA_CHAVE_SECRETA" | wrangler secret put REPORT_KEY

# 5. gera a landing embutida e publica
node build.mjs && wrangler deploy
```

Relatorio: `https://SEU-DOMINIO/__report?key=UMA_CHAVE_SECRETA`

## Etica / escopo

Use apenas em **campanhas internas autorizadas** de conscientizacao, com dominio
proprio de laboratorio. Nao aponte para dominios reais de terceiros e nao colete
senhas — a landing apenas orienta o usuario.
