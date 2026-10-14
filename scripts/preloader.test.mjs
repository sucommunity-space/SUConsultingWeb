import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const layout = fs.readFileSync('src/layouts/BaseLayout.astro', 'utf8');
const headScript = layout.match(/<script is:inline define:vars=\{\{ isHomePage \}\}>([\s\S]*?)<\/script>/)[1];
const component = fs.readFileSync('src/components/interactive/SitePreloader.astro', 'utf8');
const loaderScript = component.match(/<script is:inline>([\s\S]*?)<\/script>/)[1];

function environment({ seen = false, reduce = false, storageFails = false, svgLoads = false } = {}) {
  const classes = new Set();
  const classList = { add: value => classes.add(value), remove: value => classes.delete(value), contains: value => classes.has(value) };
  const timers = new Map();
  let removed = false;
  let fetches = 0;
  class HTMLElement {}
  class SVGElement {}
  const root = new HTMLElement();
  root.remove = () => { removed = true; };
  const host = new HTMLElement();
  const path = { getAttribute: name => name === 'd' ? 'M0 0 L10 10' : null,
    classList: { add: () => {} } };
  const svg = new SVGElement();
  svg.getAttribute = name => name === 'viewBox' ? '0 0 10 10' : null;
  svg.querySelector = () => null;
  svg.querySelectorAll = () => [path];
  host.querySelector = () => svg;
  host.querySelectorAll = () => [path];
  root.classList = { add: () => {} };
  const context = {
    isHomePage: true, HTMLElement, SVGElement, CustomEvent: class {},
    DOMParser: class { parseFromString() { return { querySelector: () => svg }; } },
    sessionStorage: {
      getItem: () => { if (storageFails) throw new Error('Storage unavailable'); return seen ? '1' : null; },
      setItem: () => { if (storageFails) throw new Error('Storage unavailable'); },
    },
    document: { documentElement: { classList }, body: { classList },
      querySelector: selector => selector === '[data-site-preloader]' ? root : host },
    window: {
      matchMedia: () => ({ matches: reduce }), dispatchEvent: () => {},
      setTimeout: (callback, delay) => { timers.set(delay, callback); return delay; },
      clearTimeout: id => timers.delete(id),
    },
    fetch: () => { fetches++; return svgLoads ? Promise.resolve({ ok: true, text: async () => '<svg />' }) : new Promise(() => {}); },
  };
  vm.createContext(context);
  vm.runInContext(headScript, context);
  vm.runInContext(loaderScript, context);
  return { classes, timers, get removed() { return removed; }, get fetches() { return fetches; } };
}

test('una descarga que no responde desbloquea la página tras el límite de descarga', () => {
  const state = environment();
  assert(state.classes.has('preloader-active'));
  assert.equal(state.fetches, 1);
  state.timers.get(2500)();
  assert(!state.classes.has('preloader-active'));
  assert(state.classes.has('preloader-complete'));
});
test('volver al inicio mantiene la animación aunque una versión anterior la marcara como vista', () => {
  const state = environment({ seen: true });
  assert.equal(state.fetches, 1);
  assert(state.classes.has('preloader-active'));
});
test('movimiento reducido evita la introducción', () => {
  const state = environment({ reduce: true });
  assert.equal(state.fetches, 0);
  assert(state.removed);
});
test('la página se desbloquea incluso si el navegador restringe sessionStorage', () => {
  const state = environment({ storageFails: true });
  state.timers.get(2500)();
  assert(state.classes.has('preloader-complete'));
});
test('el SVG cargado completa su secuencia original sin ser interrumpido a los 700 ms', async () => {
  const state = environment({ svgLoads: true });
  await new Promise(setImmediate);
  assert(!state.timers.has(700));
  assert(!state.timers.has(2500));
  assert(state.classes.has('preloader-active'));
  state.timers.get(1800)();
  state.timers.get(2800)();
  assert(state.classes.has('preloader-active'));
  state.timers.get(3800)();
  assert(state.classes.has('preloader-complete'));
});
