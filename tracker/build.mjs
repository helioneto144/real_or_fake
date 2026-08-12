// Gera src/landing.js embutindo a landing_treinamento.html como string JS.
// Rode antes de cada `wrangler deploy` se a landing mudar:  node build.mjs
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const dir = dirname(fileURLToPath(import.meta.url));
const html = readFileSync(resolve(dir, '../landing_treinamento.html'), 'utf8');
mkdirSync(resolve(dir, 'src'), { recursive: true });
writeFileSync(
  resolve(dir, 'src/landing.js'),
  'export const LANDING_HTML = ' + JSON.stringify(html) + ';\n'
);
console.log(`landing.js gerado (${html.length} bytes de HTML).`);
