const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const { pathToFileURL } = require('node:url');
const path = require('node:path');

const fixture = pathToFileURL(path.join(__dirname, 'homepage-layout.html')).href;
const widths = [320, 375, 390, 620, 768, 900, 901, 1024, 1280, 1440];

test('homepage keeps its full-width bands and responsive inner layout', async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    for (const width of widths) {
      const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
      await page.goto(fixture);
      const report = await page.evaluate(() => window.layoutReport());
      const gutter = width <= 380 ? 18 : width <= 620 ? 24 : width <= 900 ? Math.min(30, Math.max(24, width * .035)) : Math.min(40, Math.max(18, width * .03));
      assert.equal(report.document, width, `horizontal overflow at ${width}px: ${JSON.stringify(report)}`);
      assert.ok(Math.abs(report.section.x) < 1 && Math.abs(report.section.width - width) < 1, `section is not full width at ${width}px`);
      assert.ok(report.shell.x >= gutter - 1, `missing left gutter at ${width}px`);
      assert.ok(report.shell.right <= width - gutter + 1, `missing right gutter at ${width}px`);
      assert.ok(report.shell.width <= 1161, `content exceeds original maximum at ${width}px`);
      assert.equal(report.split.columns, width <= 900 ? 1 : 2, `journey grid at ${width}px`);
      assert.equal(report.hero.columns, width <= 900 ? 1 : 2, `hero grid at ${width}px`);
      assert.equal(report.cards.columns, width <= 900 ? 1 : 3, `practice cards at ${width}px`);
      assert.equal(report.contact.columns, width <= 900 ? 1 : width < 1280 ? 2 : 3, `contact grid at ${width}px`);
      await page.close();
    }
  } finally {
    await browser.close();
  }
});