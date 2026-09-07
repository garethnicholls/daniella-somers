const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const { once } = require('node:events');

const root = path.resolve(__dirname, '..');
const widths = [320, 375, 620, 900, 901, 1280, 1440];

function contentType(file) {
  return path.extname(file) === '.jpg' ? 'image/jpeg' : 'text/html; charset=utf-8';
}

async function serveSite() {
  const server = http.createServer((request, response) => {
    const pathname = new URL(request.url || '/', 'http://127.0.0.1').pathname;
    const relative = pathname === '/' ? 'index.html' : decodeURIComponent(pathname.slice(1));
    const file = path.resolve(root, relative);
    if (!file.startsWith(root + path.sep) || !fs.existsSync(file) || fs.statSync(file).isDirectory()) {
      response.writeHead(404).end();
      return;
    }
    response.writeHead(200, { 'content-type': contentType(file) });
    response.end(fs.readFileSync(file));
  });
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  return {
    url: `http://127.0.0.1:${server.address().port}/`,
    close: () => new Promise((resolve, reject) => server.close(error => error ? reject(error) : resolve())),
  };
}

test('repository has one Pages front page and the production domain', () => {
  const html = fs.readFileSync(path.join(root, 'index.html'), 'utf8');
  assert.equal(fs.readFileSync(path.join(root, 'CNAME'), 'utf8').trim(), 'daniellasomerscounselling.co.uk');
  assert.match(html, /<link rel="canonical" href="https:\/\/daniellasomerscounselling\.co\.uk\/">/);
  assert.match(html, /<img class="portrait-photo" src="assets\/daniella-hero\.jpg"/);
  assert.doesNotMatch(html, /wp-content|option-b|option-c|portrait\.css|site\.css/i);
  assert.ok(fs.existsSync(path.join(root, '.nojekyll')));
});

test('front page is responsive, complete and free of browser errors', async () => {
  const site = await serveSite();
  const browser = await chromium.launch({ headless: true });
  try {
    for (const width of widths) {
      const page = await browser.newPage({ viewport: { width, height: 900 } });
      const errors = [];
      const failed = [];
      page.on('pageerror', error => errors.push(error.message));
      page.on('requestfailed', request => failed.push(request.url()));
      const response = await page.goto(site.url, { waitUntil: 'networkidle' });
      assert.ok(response && response.ok(), `front page failed at ${width}px`);
      const report = await page.evaluate(() => ({
        documentWidth: document.documentElement.scrollWidth,
        heroColumns: getComputedStyle(document.querySelector('.hero')).gridTemplateColumns.split(' ').length,
        contactColumns: getComputedStyle(document.querySelector('.contact-grid')).gridTemplateColumns.split(' ').length,
        image: {
          complete: document.querySelector('.portrait-photo').complete,
          width: document.querySelector('.portrait-photo').naturalWidth,
        },
        sections: ['top', 'about', 'practice', 'qualifications', 'fees', 'contact'].every(id => document.getElementById(id)),
      }));
      assert.equal(report.documentWidth, width, `horizontal overflow at ${width}px`);
      assert.equal(report.heroColumns, width <= 900 ? 1 : 2, `hero columns at ${width}px`);
      assert.equal(report.contactColumns, width <= 900 ? 1 : 2, `contact columns at ${width}px`);
      assert.deepEqual(report.image, { complete: true, width: 360 });
      assert.equal(report.sections, true);
      assert.deepEqual(errors, []);
      assert.deepEqual(failed, []);
      await page.close();
    }
  } finally {
    await browser.close();
    await site.close();
  }
});
