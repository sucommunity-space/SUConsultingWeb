import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const layout = fs.readFileSync('src/layouts/BaseLayout.astro', 'utf8');
const headScript = layout.match(/<script is:inline define:vars=\{\{ isHomePage \}\}>([\s\S]*?)<\/script>/)[1];
const component = fs.readFileSync('src/components/interactive/SitePreloader.astro', 'utf8');
const loaderScript = component.match(/<script is:inline>([\s\S]*?)<\/script>/)[1];

function environment({ seen = false, reduce = false, storageFails = false } = {}) {
  const classes = new Set();
  const classList = { add: value => classes.add(value), remove: value => classes.delete(value), contains: value => classes.has(value) };
  const timers = new Map();
  let removed = false;
  let fetches = 0;
  class HTMLElement {}
  const root = new HTMLElement();
  root.remove = () => { removed = true; };
  const host = new HTMLElement();
  const context = {
    isHomePage: true, HTMLElement, CustomEvent: class {},
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
    fetch: () => { fetches++; return new Promise(() => {}); },
  };
  vm.createContext(context);
  vm.runInContext(headScript, context);
  vm.runInContext(loaderScript, context);
  return { classes, timers, get removed() { return removed; }, get fetches() { return fetches; } };
}

test('una descarga que no responde no bloquea la página más de 700 ms', () => {
  const state = environment();
  assert(state.classes.has('preloader-active'));
  assert.equal(state.fetches, 1);
  state.timers.get(700)();
  assert(!state.classes.has('preloader-active'));
  assert(state.classes.has('preloader-complete'));
});
test('volver al inicio en la misma sesión evita la espera y la descarga', () => {
  const state = environment({ seen: true });
  assert.equal(state.fetches, 0);
  assert(state.removed);
  assert(state.classes.has('preloader-complete'));
});
test('movimiento reducido evita la introducción', () => {
  const state = environment({ reduce: true });
  assert.equal(state.fetches, 0);
  assert(state.removed);
});
test('la página se desbloquea incluso si el navegador restringe sessionStorage', () => {
  const state = environment({ storageFails: true });
  state.timers.get(700)();
  assert(state.classes.has('preloader-complete'));
});
