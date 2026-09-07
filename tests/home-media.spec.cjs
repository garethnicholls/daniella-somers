const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const theme = path.resolve(__dirname, '../wp-content/themes/daniella-somers');
const read = name => fs.readFileSync(path.join(theme, name), 'utf8');
const media = read('home-media.css');
const editable = read('editable-home.css');
const gutters = read('responsive-gutters.css');
const base = read('style.css');

 test('room and badge images retain their intrinsic proportions', () => {
  assert.match(media, /\.ds-room-image img,\.ds-bacp-image img\{[^}]*height:auto!important/);
  assert.match(media, /\.ds-room-image img\{object-position:center\}/);
  assert.match(media, /\.ds-bacp-image img\{object-position:left center\}/);
  assert.doesNotMatch(editable, /\.ds-room-image\s*\{|\.ds-bacp-image\s*\{/);
  assert.doesNotMatch(media, /display:none|srcset\s*:|background-image\s*:/);
});

test('the existing 1160px composition has one responsive owner', () => {
  assert.match(gutters, /--ds-page-gutter:clamp\(18px,3vw,40px\)/);
  assert.match(gutters, /max-width:var\(--max\)/);
  assert.match(gutters, /@media\(max-width:900px\)/);
  assert.match(gutters, /@media\(max-width:620px\)/);
  assert.doesNotMatch(base + editable + gutters, /grid-template-columns:[^;}]*!important/);
  assert.doesNotMatch(base, /\.wp-site-blocks :where\(\.wp-block-group/);
  assert.doesNotMatch(editable, /\.ds-contact-layout\s*\{[^}]*grid-template-columns/);
  assert.match(gutters, /:not\(\.is-layout-flex\):not\(\.is-layout-grid\)/);
});

test('Home is rendered from saved native blocks without automatic content rewrites', () => {
  const template = read('templates/front-page.html');
  const bootstrap = read('inc/editable-home.php');
  const helpers = read('inc/home-media.php');
  assert.equal((template.match(/<!-- wp:post-content /g) || []).length, 1);
  assert.match(template, /"align":"full"/);
  assert.doesNotMatch(bootstrap, /wp_insert_post\s*\(|wp_update_post\s*\(|wp_delete_post\s*\(|update_option\s*\(/);
  assert.match(helpers, /'blockName' => 'core\/image'/);
  assert.match(helpers, /'linkDestination' => 'none'/);
});
