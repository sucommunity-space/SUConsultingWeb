import { createHash } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';

const root = path.resolve('dist');
async function walk(directory) {
  const files = [];
  for (const entry of await fs.readdir(directory, { withFileTypes: true })) {
    const file = path.join(directory, entry.name);
    files.push(...(entry.isDirectory() ? await walk(file) : [file]));
  }
  return files;
}

const replacements = new Map();
let before = 0;
let after = 0;
// Mantener los originales en public; optimizar únicamente el artefacto del build.
for (const file of await walk(path.join(root, 'images'))) {
  if (!file.endsWith('.webp')) continue;
  const original = await fs.readFile(file);
  const candidate = await sharp(original)
    .resize({ width: 1920, height: 1920, fit: 'inside', withoutEnlargement: true })
    .webp({ quality: 82, effort: 5 })
    .toBuffer();
  const output = candidate.length < original.length ? candidate : original;
  const hash = createHash('sha256').update(output).digest('hex').slice(0, 12);
  const target = file.replace(/\.webp$/, `-${hash}.webp`);
  await fs.writeFile(target, output);
  await fs.unlink(file);
  const url = '/' + path.relative(root, file).split(path.sep).join('/');
  replacements.set(url, '/' + path.relative(root, target).split(path.sep).join('/'));
  before += original.length;
  after += output.length;
}

// HTML y CSS comparten las mismas URLs con hash: el caché no mezcla releases.
const stylesheets = new Map();
for (const file of await walk(root)) {
  if (!/\.(html|css|js)$/.test(file)) continue;
  const original = await fs.readFile(file, 'utf8');
  let output = original;
  for (const [source, target] of replacements) output = output.replaceAll(source, target);
  if (output === original) continue;
  if (file.endsWith('.css')) {
    const hash = createHash('sha256').update(output).digest('hex').slice(0, 12);
    const target = file.replace(/\.css$/, `-${hash}.css`);
    await fs.writeFile(target, output);
    await fs.unlink(file);
    stylesheets.set('/' + path.relative(root, file).split(path.sep).join('/'),
      '/' + path.relative(root, target).split(path.sep).join('/'));
  } else {
    await fs.writeFile(file, output);
  }
}
for (const file of await walk(root)) {
  if (!/\.(html|js)$/.test(file)) continue;
  const original = await fs.readFile(file, 'utf8');
  let output = original;
  for (const [source, target] of stylesheets) output = output.replaceAll(source, target);
  if (output !== original) await fs.writeFile(file, output);
}
console.log(JSON.stringify({ imagesOptimized: replacements.size, originalBytes: before,
  optimizedBytes: after, reductionPercent: Math.round((1 - after / before) * 100) }));
