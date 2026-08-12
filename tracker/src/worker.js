import { LANDING_HTML } from './landing.js';

const esc = (s) =>
  String(s == null ? '' : s).replace(/[&<>"]/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])
  );

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const path = url.pathname;

    // Health check
    if (path === '/__health') return new Response('ok');

    // Relatorio protegido por segredo: /__report?key=...  (REPORT_KEY = secret do Worker)
    if (path === '/__report') {
      if (!env.REPORT_KEY || url.searchParams.get('key') !== env.REPORT_KEY) {
        return new Response('forbidden', { status: 403 });
      }
      return report(env);
    }

    // Token: ?t=<token>  ou  /t/<token>
    let token = url.searchParams.get('t');
    if (!token && path.startsWith('/t/')) token = decodeURIComponent(path.slice(3));

    // So registra clique quando ha token (evita ruido de favicon/robots/prefetch sem token)
    if (token) {
      let email = 'desconhecido', name = '', template = '';
      try {
        const rec = await env.DB
          .prepare('SELECT email, name, template FROM recipients WHERE token = ?')
          .bind(token).first();
        if (rec) { email = rec.email; name = rec.name || ''; template = rec.template || ''; }
      } catch (_) { /* segue mesmo se a leitura falhar */ }

      try {
        await env.DB.prepare(
          'INSERT INTO clicks (token,email,name,template,ts,ip,ua,country) VALUES (?,?,?,?,?,?,?,?)'
        ).bind(
          token, email, name, template,
          new Date().toISOString(),
          request.headers.get('CF-Connecting-IP') || '',
          request.headers.get('User-Agent') || '',
          (request.cf && request.cf.country) || ''
        ).run();
      } catch (_) { /* nunca deixa o log quebrar a entrega da landing */ }
    }

    // Sempre entrega a landing de conscientizacao
    return new Response(LANDING_HTML, {
      headers: { 'content-type': 'text/html; charset=utf-8' },
    });
  },
};

async function report(env) {
  const recips = await env.DB.prepare('SELECT COUNT(*) AS n FROM recipients').first();
  const clickers = await env.DB.prepare(
    `SELECT email, name, template, MIN(ts) AS first_ts, COUNT(*) AS hits, MAX(country) AS country
       FROM clicks
      WHERE token IS NOT NULL AND token <> 'sem-token'
      GROUP BY token
      ORDER BY first_ts`
  ).all();
  const logRows = await env.DB.prepare(
    `SELECT ts, email, name, template, ip, country, ua FROM clicks ORDER BY id DESC LIMIT 300`
  ).all();

  const total = recips ? recips.n : 0;
  const caiu = clickers.results.length;

  const clickersHtml = clickers.results.map((r) => `
    <tr>
      <td class="n">${esc(r.name || r.email)}</td>
      <td>${esc(r.email)}</td>
      <td>${esc(r.template)}</td>
      <td>${esc(r.first_ts)}</td>
      <td style="text-align:center">${esc(r.hits)}</td>
      <td>${esc(r.country)}</td>
    </tr>`).join('');

  const logHtml = logRows.results.map((r) => `
    <tr>
      <td>${esc(r.ts)}</td>
      <td>${esc(r.name || r.email)}</td>
      <td>${esc(r.template)}</td>
      <td><code>${esc(r.ip)}</code> ${esc(r.country)}</td>
      <td class="ua">${esc(r.ua)}</td>
    </tr>`).join('');

  const html = `<!doctype html><html lang="pt-br"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Relatorio — quem clicou</title>
<style>
  body{font:14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;margin:0;background:#0f1115;color:#e5eaf0}
  .wrap{max-width:1000px;margin:0 auto;padding:24px 16px 64px}
  h1{font-size:20px;margin:0 0 4px} h2{font-size:15px;margin:28px 0 8px;color:#9ecbff}
  .kpi{display:flex;gap:16px;margin:16px 0}
  .box{background:#182029;border:1px solid #263241;border-radius:12px;padding:16px 20px;min-width:120px}
  .box .big{font-size:30px;font-weight:800} .box.red .big{color:#ff8b80} .box .lbl{color:#8a97a5;font-size:12px}
  table{border-collapse:collapse;width:100%;font-size:13px}
  th,td{border:1px solid #263241;padding:6px 9px;text-align:left;vertical-align:top}
  th{background:#1a1f29;position:sticky;top:0}
  tr:nth-child(even){background:#141922}
  .n{color:#ff8b80;font-weight:700} code{color:#9ecbff}
  td.ua{max-width:360px;color:#8a97a5;font-size:11px;word-break:break-word}
  .empty{color:#8a97a5;padding:14px 0}
</style></head><body><div class="wrap">
  <h1>Campanha de conscientizacao — resultado</h1>
  <div class="kpi">
    <div class="box red"><div class="big">${caiu}</div><div class="lbl">clicaram (pessoas distintas)</div></div>
    <div class="box"><div class="big">${total}</div><div class="lbl">destinatarios cadastrados</div></div>
  </div>

  <h2>Quem clicou</h2>
  ${caiu ? `<table><thead><tr><th>Pessoa</th><th>E-mail</th><th>Template</th><th>1o clique (UTC)</th><th>Cliques</th><th>Pais</th></tr></thead><tbody>${clickersHtml}</tbody></table>`
         : `<div class="empty">Ninguem clicou ainda.</div>`}

  <h2>Log completo (ultimos 300)</h2>
  ${logRows.results.length ? `<table><thead><tr><th>Quando (UTC)</th><th>Pessoa</th><th>Template</th><th>Origem</th><th>User-Agent</th></tr></thead><tbody>${logHtml}</tbody></table>`
         : `<div class="empty">Sem registros.</div>`}
</div></body></html>`;

  return new Response(html, { headers: { 'content-type': 'text/html; charset=utf-8' } });
}
