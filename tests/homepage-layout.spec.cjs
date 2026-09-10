const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const { pathToFileURL } = require('node:url');
const path = require('node:path');

const fixture = pathToFileURL(path.join(__dirname, 'homepage-layout.html')).href;
const widths = [320, 375, 390, 480, 620, 768, 900, 901, 1024, 1280, 1440, 1680];

test('homepage keeps its full-width bands and responsive inner layout', async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    for (const width of widths) {
      const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
      await page.goto(fixture);
      const report = await page.evaluate(() => window.layoutReport());
      const gutter = width <= 480 ? 12 : 18;
      const details = `${width}px: ${JSON.stringify(report)}`;
      assert.equal(report.document, width, `horizontal overflow: ${details}`);
      assert.ok(Math.abs(report.section.x) < 1 && Math.abs(report.section.width - width) < 1, `section is not full width: ${details}`);
      assert.ok(Math.abs(parseFloat(report.section.padding) - gutter) < 1, `incorrect section padding: ${details}`);
      assert.ok(report.shell.x >= gutter - 1, `missing left gutter: ${details}`);
      assert.ok(report.shell.right <= width - gutter + 1, `missing right gutter: ${details}`);
      assert.ok(report.shell.width <= 1161, `content exceeds approved maximum: ${details}`);
      assert.equal(report.split.columns, width <= 900 ? 1 : 2, `journey grid: ${details}`);
      assert.equal(report.hero.columns, width <= 900 ? 1 : 2, `hero grid: ${details}`);
      assert.equal(report.cards.columns, width <= 900 ? 1 : 3, `practice cards: ${details}`);
      if (width > 900) assert.ok(Math.max(...report.cardTops) - Math.min(...report.cardTops) < 1, `card tops are not aligned: ${details}`);
      assert.equal(report.contact.columns, width <= 900 ? 1 : 2, `contact grid: ${details}`);
      await page.close();
    }
  } finally {
    await browser.close();
  }
});
