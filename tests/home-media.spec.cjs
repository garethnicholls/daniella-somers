const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const theme = path.resolve(__dirname, '../wp-content/themes/daniella-somers');
const read = name => fs.readFileSync(path.join(theme, name), 'utf8');
const base = read('style.css');

test('room and badge images retain their intrinsic proportions', () => {
  assert.match(base, /\.ds-room-image img,\.ds-bacp-image img\{[^}]*height:auto/);
  assert.match(base, /\.ds-room-image img\{object-position:center\}/);
  assert.match(base, /\.ds-bacp-image img\{object-position:left center\}/);
  assert.doesNotMatch(base, /#about:after/);
  assert.doesNotMatch(base, /#fees\s*>\s*\.wp-block-image\s*\{[^}]*display:none/);
});

test('the existing 1160px composition has one responsive owner', () => {
  assert.match(base, /--ds-page-gutter:clamp\(20px,3vw,40px\)/);
  assert.match(base, /--max:1160px/);
  assert.match(base, /@media\(max-width:900px\)/);
  assert.match(base, /@media\(max-width:480px\)/);
  assert.doesNotMatch(base, /grid-template-columns:[^;}]*!important/);
  assert.doesNotMatch(base, /\.wp-site-blocks :where\(\.wp-block-group/);
  assert.match(base, /:not\(\.is-layout-flex\):not\(\.is-layout-grid\)/);
});

test('the saved room and accreditation images are part of the published composition', () => {
  assert.match(base, /#fees:not\(:has\(>\.ds-shell\)\)>\.wp-block-image\{/);
  assert.match(base, /#fees:not\(:has\(>\.ds-shell\)\)>\.wp-block-image img\{/);
  assert.match(base, /\.home \.ds-contact-layout>\.ds-contact-trust,[\s\S]*display:grid/);
  assert.match(base, /\.ds-contact-trust \.ds-trust-card\{display:grid/);
  assert.match(base, /\.ds-contact-layout>\.ds-contact-trust\{grid-template-columns:minmax\(0,1fr\)\}/);
  assert.match(base, /@media\(max-width:900px\)[\s\S]*\.ds-contact-trust \.ds-trust-card\{grid-template-columns:minmax\(0,1fr\)/);
  assert.doesNotMatch(base, /\.ds-contact-trust:not\(\.is-layout-flex\):not\(\.is-layout-grid\)\{grid-template-columns:repeat\(2/);
  assert.doesNotMatch(base, /\.home \.ds-contact-layout>\.ds-contact-trust\{display:none\}/);
});

test('known homepage media receives accessible fallback text without replacing editor content', () => {
  const functions = read('functions.php');
  assert.match(functions, /render_block_core\/image/);
  assert.match(functions, /Daniella Somers, counsellor and psychotherapist/);
  assert.match(functions, /The calm counselling room used for in-person sessions/);
  assert.match(functions, /BACP Registered Member, MBACP, accredited register mark/);
});

test('Home is rendered from saved native blocks without automatic content rewrites', () => {
  const template = read('templates/front-page.html');
  const bootstrap = read('inc/editable-home.php');
  const helpers = read('inc/home-media.php');
  assert.equal((template.match(/<!-- wp:post-content /g) || []).length, 1);
  assert.match(template, /"align":"full"/);
  assert.doesNotMatch(bootstrap, /wp_insert_post\s*\(|wp_update_post\s*\(|wp_delete_post\s*\(|update_option\s*\(/);
  assert.match(bootstrap, /register_block_pattern\('daniella-somers\/front-page'/);
  assert.match(helpers, /'blockName' => 'core\/image'/);
  assert.match(helpers, /'linkDestination' => 'none'/);
});

test('wrapperless saved blocks recover only when the shell is actually missing', () => {
  assert.match(base, /#practice:not\(:has\(> \.ds-shell\)\)/);
  assert.match(base, /:is\(#about,#qualifications,#fees\):not\(:has\(> \.ds-shell\)\)/);
  assert.match(base, /padding-inline:max\(var\(--ds-page-gutter\),calc\(\(100% - var\(--max\)\)\/2\)\)/);
  assert.match(base, /editor-styles-wrapper:has\(#top\)/);
  assert.doesNotMatch(base, /\.home :is\(#about,#qualifications,#fees\)\{\s*display:grid/);
});
