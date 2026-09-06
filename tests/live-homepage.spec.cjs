const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const theme = path.resolve(__dirname, '../wp-content/themes/daniella-somers');
const styles = new Set(['style.css', 'exact.css', 'editable-home.css', 'responsive-gutters.css', 'home-media.css']);
const widths = [320, 375, 390, 620, 768, 900, 901, 1024, 1280, 1440];
const origin = process.env.HOMEPAGE_URL || 'https://daniellasomerscounselling.co.uk/';

test('real homepage with proposed CSS preserves layout and images', async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    for (const width of widths) {
      const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
      const failed = [];
      page.on('pageerror', error => failed.push(error.message));
      await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        const name = path.posix.basename(url.pathname);
        if (url.pathname.includes('/wp-content/themes/daniella-somers/') && styles.has(name)) {
          return route.fulfill({ status: 200, contentType: 'text/css', body: fs.readFileSync(path.join(theme, name), 'utf8') });
        }
        return route.continue();
      });
      const response = await page.goto(origin, { waitUntil: 'domcontentloaded', timeout: 45000 });
      assert.ok(response && response.ok(), `Homepage did not load at ${width}px`);
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
      assert.equal(report.document, width, `Horizontal overflow at ${width}px`);
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
      await page.close();
    }
  } finally {
    await browser.close();
  }
});
