const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const { pathToFileURL } = require('node:url');
const path = require('node:path');

const fixture = pathToFileURL(path.join(__dirname, 'homepage-layout.html')).href;
const widths = [320, 375, 390, 620, 768, 900, 901, 1024, 1280, 1440];

 test('the native editor uses the same homepage geometry as the public page', async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    for (const width of widths) {
      const page = await browser.newPage({ viewport: { width, height: 900 } });
      await page.goto(fixture);
      const publicLayout = await page.evaluate(() => window.layoutReport());
      await page.evaluate(() => {
        const content = document.querySelector('.wp-block-post-content');
        const editor = document.createElement('div');
        editor.className = 'editor-styles-wrapper';
        document.body.classList.remove('home');
        content.replaceWith(editor);
        editor.append(content);
      });
      const editorLayout = await page.evaluate(() => window.layoutReport());
      for (const key of ['section', 'shell', 'hero', 'split', 'cards', 'contact']) {
        assert.ok(Math.abs(publicLayout[key].width - editorLayout[key].width) < 1, `${key} width differs at ${width}px`);
        assert.ok(Math.abs(publicLayout[key].x - editorLayout[key].x) < 1, `${key} position differs at ${width}px`);
        assert.equal(publicLayout[key].columns, editorLayout[key].columns, `${key} columns differ at ${width}px`);
      }
      assert.equal(editorLayout.document, width, `editor horizontal overflow at ${width}px`);
      await page.close();
    }
  } finally {
    await browser.close();
  }
});

test('an explicit Gutenberg flex or grid choice is not replaced by theme defaults', async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    await page.goto(fixture);
    await page.addStyleTag({ content: '.is-layout-flex{display:flex}.is-layout-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr))}' });
    const result = await page.evaluate(() => {
      const split = document.querySelector('#about .ds-split');
      const cards = document.querySelector('#practice .ds-card-grid');
      split.classList.add('is-layout-flex');
      cards.classList.add('is-layout-grid');
      return {
        split: getComputedStyle(split).display,
        cards: getComputedStyle(cards).display,
        columns: getComputedStyle(cards).gridTemplateColumns.split(' ').length,
      };
    });
    assert.equal(result.split, 'flex');
    assert.equal(result.cards, 'grid');
    assert.equal(result.columns, 4);
  } finally {
    await browser.close();
  }
});
