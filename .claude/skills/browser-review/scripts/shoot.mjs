// End-to-end screenshot sweep across a viewport matrix. Runs inside the Sail
// `app` container:  ./vendor/bin/sail exec app node .claude/skills/browser-review/scripts/shoot.mjs
// Env: VIEWPORTS=mobile,tablet,desktop,wide (default all)  BASE=http://localhost  OUT=storage/app/browser-review
// Pages are discovered from `artisan route:list` (see lib.mjs) — nothing hardcoded.
import { chromium } from 'playwright';
import { BASE, VIEWPORT_DEFS, parseViewports, login, dismissReveal, discoverPageRoutes } from './lib.mjs';

const OUT = process.env.OUT ?? 'storage/app/browser-review';
const selected = parseViewports();

const browser = await chromium.launch({
  executablePath: '/usr/bin/chromium',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

for (const vp of selected) {
  const def = VIEWPORT_DEFS[vp];
  const dir = `${OUT}/${vp}`;
  const errors = [];
  const context = await browser.newContext(def);
  const page = await context.newPage();
  page.on('console', (m) => { if (m.type() === 'error') errors.push(`[console] ${page.url()} :: ${m.text()}`); });
  page.on('pageerror', (e) => errors.push(`[pageerror] ${page.url()} :: ${e.message}`));

  console.log(`\n=== ${vp} (${def.viewport.width}x${def.viewport.height}) ===`);
  // Guest login page first, then authenticate and discover the rest.
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await page.screenshot({ path: `${dir}/00-login-full.png`, fullPage: true });
  await login(page);
  await dismissReveal(page);
  const routes = await discoverPageRoutes(page);
  console.log(`  discovered ${routes.length} pages`);

  const seen = new Set();
  let i = 1;
  for (const { name, path } of routes) {
    try {
      await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle', timeout: 20000 });
      const landed = new URL(page.url()).pathname;
      if (seen.has(landed)) { continue; }            // dedupe redirects to an already-shot page
      seen.add(landed);
      await page.waitForTimeout(800);
      const idx = String(i).padStart(2, '0');
      await page.screenshot({ path: `${dir}/${idx}-${name}-viewport.png`, fullPage: false });
      await page.screenshot({ path: `${dir}/${idx}-${name}-full.png`, fullPage: true });
      console.log(`  shot ${idx}-${name} (${path})`);
      i++;
    } catch (e) {
      errors.push(`[navfail] ${path} :: ${e.message}`);
      console.log(`  FAIL ${name} (${path}): ${e.message}`);
    }
  }
  console.log(errors.length ? `  JS errors:\n   ${errors.join('\n   ')}` : '  JS errors: none');
  await context.close();
}

await browser.close();
console.log(`\nDone. Screenshots under ${OUT}/<viewport>/ — read the PNGs to inspect.`);
