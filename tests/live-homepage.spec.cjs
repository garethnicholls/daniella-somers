const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const http = require('node:http');
const { once } = require('node:events');
const fs = require('node:fs');
const path = require('node:path');

const theme = path.resolve(__dirname, '../wp-content/themes/daniella-somers');
const styles = ['style.css'];
const stylesheetNames = new Set(styles);
const widths = [320, 375, 390, 620, 768, 900, 901, 1024, 1280, 1440];
const externalOrigin = process.env.HOMEPAGE_URL;

function homepageDocument() {
  const content = fs.readFileSync(path.join(theme, 'content/home-page.html'), 'utf8');
  const links = styles
    .map(file => `<link rel="stylesheet" href="/wp-content/themes/daniella-somers/${file}">`)
    .join('');
  return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daniella Somers homepage</title>${links}</head><body class="home"><div class="wp-site-blocks"><div class="wp-block-post-content is-layout-constrained">${content}</div></div></body></html>`;
}

function contentType(file) {
  switch (path.extname(file).toLowerCase()) {
    case '.css': return 'text/css; charset=utf-8';
    case '.jpg':
    case '.jpeg': return 'image/jpeg';
    case '.png': return 'image/png';
    case '.webp': return 'image/webp';
    case '.svg': return 'image/svg+xml';
    default: return 'application/octet-stream';
  }
}

async function startHomepageServer() {
  const document = homepageDocument();
  const server = http.createServer((request, response) => {
    const url = new URL(request.url || '/', 'http://127.0.0.1');
    if (url.pathname === '/' || url.pathname === '/index.html') {
      response.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
      response.end(document);
      return;
    }
    const prefix = '/wp-content/themes/daniella-somers/';
    if (!url.pathname.startsWith(prefix)) {
      response.writeHead(404).end();
      return;
    }
    const relative = decodeURIComponent(url.pathname.slice(prefix.length));
    const file = path.resolve(theme, relative);
    if (!file.startsWith(theme + path.sep) || !fs.existsSync(file) || fs.statSync(file).isDirectory()) {
      response.writeHead(404).end();
      return;
    }
    response.writeHead(200, { 'content-type': contentType(file) });
    response.end(fs.readFileSync(file));
  });
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  const { port } = server.address();
  return {
    origin: `http://127.0.0.1:${port}/`,
    close: () => new Promise((resolve, reject) => server.close(error => error ? reject(error) : resolve())),
  };
}

test('homepage markup with proposed CSS preserves layout and images', async () => {
  const homepage = externalOrigin ? null : await startHomepageServer();
  const origin = externalOrigin || homepage.origin;
  const browser = await chromium.launch({ headless: true });
  try {
    for (const width of widths) {
      const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
      const failed = [];
      page.on('pageerror', error => failed.push(error.message));
      try {
        if (externalOrigin) {
          await page.route('**/*', async route => {
            const url = new URL(route.request().url());
            const name = path.posix.basename(url.pathname);
            if (url.pathname.includes('/wp-content/themes/daniella-somers/') && stylesheetNames.has(name)) {
              return route.fulfill({ status: 200, contentType: 'text/css', body: fs.readFileSync(path.join(theme, name), 'utf8') });
            }
            return route.continue();
          });
        }
        const response = await page.goto(origin, { waitUntil: 'domcontentloaded', timeout: 45000 });
        assert.ok(response && response.ok(), `Homepage did not load at ${width}px${response ? ` (${response.status()} ${response.statusText()})` : ''}`);
        await page.evaluate(() => document.fonts.ready);
        await page.waitForTimeout(300);
        const report = await page.evaluate(() => {
          const box = element => {
            if (!element) return null;
            const r = element.getBoundingClientRect();
            const s = getComputedStyle(element);
            return { x: r.x, right: r.right, width: r.width, padding: s.paddingLeft, columns: s.gridTemplateColumns.split(' ').length };
          };
          const section = document.querySelector('#about');
          const images = [...document.querySelectorAll('main img')].map(img => ({ src: img.currentSrc || img.src, alt: img.alt, complete: img.complete, width: img.naturalWidth, height: img.naturalHeight }));
          return { document: document.documentElement.scrollWidth, viewport: innerWidth, section: box(section), shell: box(section?.querySelector('.ds-shell')), hero: box(document.querySelector('.ds-hero-grid')), split: box(document.querySelector('#about .ds-split')), cards: box(document.querySelector('#practice .ds-card-grid')), contact: box(document.querySelector('.ds-contact-layout')), images };
        });
        console.log(`${width}px: ${JSON.stringify(report)}`);
        assert.ok(report.section && report.shell, 'Missing original homepage sections');
        assert.ok(Math.abs(report.section.x) < 1 && Math.abs(report.section.width - width) < 1, 'Section is not full width');
        assert.ok(report.shell.x >= 17 && report.shell.right <= width - 17, 'Missing content gutters');
        assert.ok(report.shell.width <= 1161, 'Content exceeds original maximum');
        assert.equal(report.split.columns, width <= 900 ? 1 : 2, 'Journey layout');
        assert.equal(report.hero.columns, width <= 900 ? 1 : 2, 'Hero layout');
        assert.equal(report.cards.columns, width <= 900 ? 1 : 3, 'Practice cards');
        assert.equal(report.contact.columns, width <= 900 ? 1 : width < 1280 ? 2 : 3, 'Contact layout');
        assert.ok(report.images.length > 0, 'No homepage images found');
        const broken = report.images.filter(image => image.complete && image.width === 0);
        assert.deepEqual(broken, [], 'Broken image URLs');
        assert.deepEqual(failed, [], 'Browser JavaScript errors');
      } finally {
        await page.close();
      }
    }
  } finally {
    await browser.close();
    if (homepage) {
      await homepage.close();
    }
  }
});
