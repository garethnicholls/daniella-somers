const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const theme = path.resolve(__dirname, '../wp-content/themes/daniella-somers');
const media = fs.readFileSync(path.join(theme, 'home-media.css'), 'utf8');
const editable = fs.readFileSync(path.join(theme, 'editable-home.css'), 'utf8');
const gutters = fs.readFileSync(path.join(theme, 'responsive-gutters.css'), 'utf8');

test('room and badge images use intrinsic height and one stylesheet', () => {
  assert.match(media, /\.ds-room-image img,\.ds-bacp-image img\{[^}]*height:auto!important/);
  assert.match(media, /\.ds-room-image img\{object-position:center\}/);
  assert.match(media, /\.ds-bacp-image img\{object-position:left center\}/);
  assert.doesNotMatch(editable, /\.ds-room-image\s*\{|\.ds-room-image img\s*\{|\.ds-bacp-image\s*\{|\.ds-bacp-image img\s*\{/);
  assert.doesNotMatch(media, /display:none|srcset\s*:|background-image\s*:/);
});

test('approved responsive layout remains unchanged', () => {
  assert.match(gutters, /--ds-page-gutter:clamp\(18px,3vw,40px\)/);
  assert.match(gutters, /max-width:var\(--max\)!important/);
  assert.match(gutters, /@media\(max-width:900px\)/);
  assert.match(gutters, /@media\(max-width:620px\)/);
});

test('WordPress image blocks remain native and do not require device-specific uploads', () => {
  const php = fs.readFileSync(path.join(theme, 'inc/home-media.php'), 'utf8');
  assert.match(php, /'blockName' => 'core\/image'/);
  assert.match(php, /'linkDestination' => 'none'/);
});
