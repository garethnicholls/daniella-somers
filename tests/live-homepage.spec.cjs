const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const http = require('node:http');
const { once } = require('node:events');
const fs = require('node:fs');
const path = require('node:path');

const theme = path.resolve(__dirname, '../wp-content/themes/daniella-somers');
const stylesheetNames = new Set(['style.css']);
const widths = [320, 375, 390, 480, 620, 768, 900, 901, 1024, 1280, 1440, 1680];
const externalOrigin = process.env.HOMEPAGE_URL;

function homepageDocument() {
  const content = fs.readFileSync(path.join(theme, 'content/home-page.html'), 'utf8');
  return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daniella Somers homepage</title><link rel="stylesheet" href="/wp-content/themes/daniella-somers/style.css"></head><body class="home"><div class="wp-site-blocks"><div class="wp-block-post-content is-layout-constrained">${content}</div></div></body></html>`;
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

test(externalOrigin ? 'proposed CSS keeps the production saved Front Page neat at every width' : 'homepage markup keeps the approved responsive composition', async (t) => {
  const homepage = externalOrigin ? null : await startHomepageServer();
  const origin = externalOrigin || homepage.origin;
  const browser = await chromium.launch({ headless: true });
  try {
    for (const width of widths) {
      const page = await browser.newPage({ viewport: { width, height: 1000 }, deviceScaleFactor: 1 });
      const pageErrors = [];
      page.on('pageerror', error => pageErrors.push(error.message));
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

        let response;
        const attempts = externalOrigin ? 3 : 1;
        for (let attempt = 0; attempt < attempts; attempt += 1) {
          response = await page.goto(origin, { waitUntil: 'domcontentloaded', timeout: 45000 });
          if (response?.ok()) break;
          if (attempt + 1 < attempts) await page.waitForTimeout(1500 * (attempt + 1));
        }
        if (externalOrigin && (!response || !response.ok())) {
          t.skip(`Production host is unavailable to the GitHub runner (${response?.status() || 'no response'})`);
          return;
        }
        assert.ok(response && response.ok(), `Homepage did not load at ${width}px`);
        await page.evaluate(() => document.fonts.ready);
        await page.waitForTimeout(250);

        const report = await page.evaluate(() => {
          const rect = element => {
            if (!element) return null;
            const r = element.getBoundingClientRect();
            const s = getComputedStyle(element);
            return {
              x: r.x,
              right: r.right,
              width: r.width,
              paddingLeft: parseFloat(s.paddingLeft) || 0,
              columns: s.display === 'grid' ? s.gridTemplateColumns.split(' ').filter(Boolean).length : 1,
            };
          };

          const mediaRect = element => {
            if (!element) return null;
            const r = element.getBoundingClientRect();
            const s = getComputedStyle(element);
            return {
              x: r.x,
              right: r.right,
              width: r.width,
              height: r.height,
              display: s.display,
              columns: s.display === 'grid' ? s.gridTemplateColumns.split(' ').filter(Boolean).length : 1,
            };
          };

          const visible = element => {
            const s = getComputedStyle(element);
            const r = element.getBoundingClientRect();
            return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0;
          };

          const contentBounds = section => {
            if (!section) return null;
            const shell = [...section.children].find(child => child.classList?.contains('ds-shell'));
            if (shell) return rect(shell);
            const children = [...section.children].filter(visible);
            if (!children.length) return rect(section);
            const boxes = children.map(child => child.getBoundingClientRect());
            const left = Math.min(...boxes.map(box => box.left));
            const right = Math.max(...boxes.map(box => box.right));
            return { x: left, right, width: right - left };
          };

          const section = id => document.getElementById(id);
          const about = section('about');
          const contact = section('contact');
          const aboutLayout = about?.querySelector('.ds-split') || (getComputedStyle(about || document.body).display === 'grid' ? about : null);
          const contactLayout = contact?.querySelector('.ds-contact-grid,.ds-contact-layout') || (getComputedStyle(contact || document.body).display === 'grid' ? contact : null);
          const heroImage = document.querySelector('.ds-hero img,.ds-portrait img');
          const feeCards = [...document.querySelectorAll('#fees .ds-card-grid>.wp-block-group,#fees .ds-card-grid>.ds-card')];

          return {
            viewport: innerWidth,
            documentWidth: document.documentElement.scrollWidth,
            sections: ['about', 'practice', 'qualifications', 'fees', 'contact'].map(id => ({
              id,
              box: rect(section(id)),
              content: contentBounds(section(id)),
            })),
            hero: rect(document.querySelector('.ds-hero-grid')),
            about: rect(aboutLayout),
            cards: rect(document.querySelector('#practice .ds-card-grid')),
            contact: rect(contactLayout),
            feeTops: feeCards.map(card => card.getBoundingClientRect().top),
            heroImage: heroImage ? { complete: heroImage.complete, width: heroImage.naturalWidth } : null,
            room: mediaRect(document.querySelector('#fees>.wp-block-image')),
            trust: mediaRect(document.querySelector('.ds-contact-layout>.ds-contact-trust')),
            trustCard: mediaRect(document.querySelector('.ds-contact-trust>.ds-trust-card')),
            certificateImage: (() => {
              const image = document.querySelector('.ds-contact-trust img');
              return image ? { complete: image.complete, width: image.naturalWidth, renderedWidth: image.getBoundingClientRect().width } : null;
            })(),
          };
        });

        const details = `${width}px: ${JSON.stringify(report)}`;
        const gutter = width <= 480 ? 18 : Math.min(40, Math.max(20, width * 0.03));
        assert.equal(report.documentWidth, width, `horizontal overflow: ${details}`);
        assert.ok(report.hero, `missing hero grid: ${details}`);
        assert.equal(report.hero.columns, width <= 900 ? 1 : 2, `hero columns: ${details}`);
        assert.ok(report.about, `missing journey layout: ${details}`);
        assert.equal(report.about.columns, width <= 900 ? 1 : 2, `journey columns: ${details}`);
        assert.ok(report.cards, `missing practice cards: ${details}`);
        assert.equal(report.cards.columns, width <= 900 ? 1 : 3, `practice card columns: ${details}`);
        assert.ok(report.contact, `missing contact layout: ${details}`);
        assert.equal(report.contact.columns, width <= 900 ? 1 : 2, `contact columns: ${details}`);

        for (const item of report.sections) {
          assert.ok(item.box && item.content, `missing ${item.id}: ${details}`);
          assert.ok(Math.abs(item.box.x) < 1 && Math.abs(item.box.width - width) < 1, `${item.id} is not full width: ${details}`);
          assert.ok(item.content.x >= gutter - 1, `${item.id} missing left gutter: ${details}`);
          assert.ok(item.content.right <= width - gutter + 1, `${item.id} missing right gutter: ${details}`);
          assert.ok(item.content.width <= 1161, `${item.id} exceeds 1160px content width: ${details}`);
        }

        if (width > 900 && report.feeTops.length > 1) {
          assert.ok(Math.max(...report.feeTops) - Math.min(...report.feeTops) < 1, `fee card tops are not aligned: ${details}`);
        }
        if (!externalOrigin) {
          assert.deepEqual(report.heroImage, { complete: true, width: 360 }, `bundled hero image is broken: ${details}`);
        } else {
          assert.ok(report.room && report.room.width > 0 && report.room.height > 0, `room image is hidden: ${details}`);
          assert.ok(report.room.x >= gutter - 1 && report.room.right <= width - gutter + 1, `room image leaves the content gutter: ${details}`);
          const expectedRatio = width <= 480 ? 4 / 3 : width <= 900 ? 16 / 10 : 2.15;
          assert.ok(Math.abs(report.room.width / report.room.height - expectedRatio) < 0.08, `room image crop is wrong: ${details}`);
          assert.ok(report.trust && report.trust.display === 'grid' && report.trust.width > 0, `accreditation panel is hidden: ${details}`);
          assert.ok(Math.abs(report.trust.width - report.contact.width) < 1, `accreditation panel is not aligned with contact content: ${details}`);
          assert.equal(report.trustCard.columns, width <= 700 ? 1 : 2, `accreditation layout is wrong: ${details}`);
          assert.ok(report.certificateImage?.complete && report.certificateImage.width > 0 && report.certificateImage.renderedWidth > 0, `certificate image is broken: ${details}`);
        }
        assert.deepEqual(pageErrors, [], `browser errors: ${details}`);
      } finally {
        await page.close();
      }
    }
  } finally {
    await browser.close();
    if (homepage) await homepage.close();
  }
});
