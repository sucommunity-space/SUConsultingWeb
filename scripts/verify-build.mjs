import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve('dist');
const origin = 'https://suconsulting.es';
const walk = dir => fs.readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
  const file = path.join(dir, entry.name);
  return entry.isDirectory() ? walk(file) : [file];
});
const files = walk(root);
const pages = files.filter(file => file.endsWith('.html'));
assert(pages.length >= 8, 'Faltan páginas institucionales compiladas.');
for (const file of files) {
  assert(!/\.(php|env|sql|map)$/.test(file), 'No incluir archivos de servidor o secretos.');
}
let references = 0;
for (const file of pages) {
  const html = fs.readFileSync(file, 'utf8');
  const relative = path.relative(root, file).replaceAll(path.sep, '/');
  const base = new URL(relative.replace(/index\.html$/, ''), `${origin}/`);
  assert(html.includes('<html lang="es"'), `Idioma inesperado: ${relative}`);
  if (html.includes('rel="canonical"')) assert(html.includes(`rel="canonical" href="${origin}`), `Canonical incorrecto: ${relative}`);
  for (const match of html.matchAll(/\b(?:href|src|poster)="([^"]+)"/g)) {
    const value = match[1].replaceAll('&amp;', '&');
    if (!value || /^(#|mailto:|tel:|data:|javascript:)/.test(value)) continue;
    const url = new URL(value, base);
    if (url.origin !== origin) continue;
    const candidate = path.resolve(root, `.${decodeURIComponent(url.pathname)}`);
    assert(candidate === root || candidate.startsWith(root + path.sep), 'Ruta fuera del build.');
    assert([candidate, path.join(candidate, 'index.html'), candidate + '.html'].some(p => fs.existsSync(p) && fs.statSync(p).isFile()),
      `Recurso o enlace local inexistente: ${relative} -> ${url.pathname}`);
    references++;
  }
}
assert(fs.readFileSync(path.join(root, 'robots.txt'), 'utf8').includes(`${origin}/sitemap-index.xml`));
assert(fs.readFileSync(path.join(root, 'sitemap-0.xml'), 'utf8').includes(`${origin}/`));
console.log(`Build verificado: ${pages.length} páginas, ${references} referencias internas; dominio y assets correctos.`);
