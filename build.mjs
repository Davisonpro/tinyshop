import * as esbuild from 'esbuild';
import * as sass from 'sass';
import { readdirSync, readFileSync, writeFileSync, existsSync, watch } from 'fs';
import { basename, join, resolve } from 'path';

const isWatch = process.argv.includes('--watch');

// ── Helpers ──

function findFiles(dir, ext) {
  if (!existsSync(dir)) return [];
  return readdirSync(dir)
    .filter(f => f.endsWith(ext) && !f.startsWith('_'))
    .map(f => join(dir, f));
}

// ── JS bundle definitions (assets/js/ → public/js/) ──

const SRC_JS_DIR = 'assets/js';

const jsBundles = {
  app: [
    'assets/js/core/namespace.js',
    'assets/js/core/network.js',
    'assets/js/core/csrf.js',
    'assets/js/core/toast.js',
    'assets/js/core/navigate.js',
    'assets/js/components/login-modal.js',
    'assets/js/components/share-sheet.js',
    'assets/js/components/image-viewer.js',
    'assets/js/shop/card-render.js',
    'assets/js/shop/init-shop.js',
    'assets/js/spa/router.js',
    'assets/js/core/boot.js',
  ],
  dashboard: [
    'assets/js/dashboard/utils.js',
    'assets/js/dashboard/api.js',
    'assets/js/dashboard/modal.js',
    'assets/js/dashboard/autosize.js',
    'assets/js/dashboard/product-list.js',
    'assets/js/dashboard/product-form.js',
    'assets/js/dashboard/init.js',
  ],
  cart:    ['assets/js/pages/cart.js'],
  auth:    ['assets/js/pages/auth.js'],
  help:    ['assets/js/pages/help.js'],
  landing: ['assets/js/pages/landing.js'],
  pricing: ['assets/js/pages/pricing.js'],
};

const jsEntries = Object.entries(jsBundles).map(([name, files]) => ({
  name,
  files,
  outMin: `public/js/${name}.min.js`,
  outDev: `public/js/${name}.js`,
}));

// ── SCSS entries (assets/css/ → public/css/) ──

const scssEntries = findFiles('assets/css', '.scss').map(src => ({
  name: basename(src, '.scss'),
  src,
  outMin: `public/css/${basename(src, '.scss')}.min.css`,
  outDev: `public/css/${basename(src, '.scss')}.css`,
}));

// ── Build functions ──

async function buildJsBundle(entry) {
  const contents = entry.files
    .map(f => readFileSync(f, 'utf8'))
    .join('\n');

  await Promise.all([
    esbuild.build({
      stdin: { contents, loader: 'js', resolveDir: '.' },
      outfile: entry.outMin,
      bundle: false,
      minify: true,
      target: ['es2015'],
      logLevel: 'warning',
    }),
    esbuild.build({
      stdin: { contents, loader: 'js', resolveDir: '.' },
      outfile: entry.outDev,
      bundle: false,
      minify: false,
      target: ['es2015'],
      logLevel: 'warning',
    }),
  ]);
}

function buildScssEntry(entry) {
  // Compile SCSS → CSS (expanded for dev)
  const result = sass.compile(entry.src, { style: 'expanded' });
  writeFileSync(entry.outDev, result.css);

  // Compile SCSS → CSS (compressed for production)
  const minResult = sass.compile(entry.src, { style: 'compressed' });
  writeFileSync(entry.outMin, minResult.css);
}

async function buildAll() {
  const start = Date.now();

  // SCSS is synchronous, JS is async
  scssEntries.forEach(buildScssEntry);
  await Promise.all(jsEntries.map(buildJsBundle));

  console.log(`Done in ${Date.now() - start}ms (${jsEntries.length} JS + ${scssEntries.length} SCSS)`);
}

// ── Watch mode ──

function watchJs() {
  const srcDir = resolve(SRC_JS_DIR);
  let debounce = null;

  watch(srcDir, { recursive: true }, (_, filename) => {
    if (!filename || !filename.endsWith('.js')) return;

    clearTimeout(debounce);
    debounce = setTimeout(async () => {
      const changedPath = join(SRC_JS_DIR, filename).replace(/\\/g, '/');
      const affected = jsEntries.filter(
        e => e.files.some(f => f.replace(/\\/g, '/') === changedPath)
      );

      if (affected.length === 0) return;

      const names = affected.map(b => b.name).join(', ');
      console.log(`Rebuilding JS: ${names}`);
      try {
        await Promise.all(affected.map(buildJsBundle));
        console.log(`Rebuilt ${names}`);
      } catch (err) {
        console.error(`Build error:`, err.message);
      }
    }, 50);
  });
}

function watchScss() {
  const dirs = ['assets/css'].filter(existsSync);

  dirs.forEach(dir => {
    watch(resolve(dir), (_, filename) => {
      if (!filename || !filename.endsWith('.scss')) return;

      // Partials (_name.scss) trigger rebuild of all entries in that dir
      const isPartial = basename(filename).startsWith('_');
      const affected = isPartial
        ? scssEntries.filter(e => e.src.startsWith(dir))
        : scssEntries.filter(e => e.src === join(dir, filename));

      if (affected.length === 0) return;

      const names = affected.map(e => e.name).join(', ');
      console.log(`Rebuilding SCSS: ${names}`);
      try {
        affected.forEach(buildScssEntry);
        console.log(`Rebuilt ${names}`);
      } catch (err) {
        console.error(`Build error:`, err.message);
      }
    });
  });
}

// ── Main ──

async function main() {
  console.log('Building assets/ → public/');
  await buildAll();

  if (isWatch) {
    watchJs();
    watchScss();
    console.log('Watching for changes... (Ctrl+C to stop)');
  }
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
