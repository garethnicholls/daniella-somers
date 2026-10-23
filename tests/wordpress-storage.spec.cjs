const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

test('uploads require persistent storage before startup', () => {
  const script = read('railway/start-wordpress.sh');
  assert.match(script, /mountpoint -q "\$UPLOADS"/);
  assert.match(script, /UPLOADS=\$ROOT\/wp-content\/uploads/);
  assert.match(script, /www-data -c "test -w/);
  assert.doesNotMatch(script, /rm -rf[^\n]*uploads|chown -R[^\n]*uploads/);
});

test('existing content and configuration are not reset at startup', () => {
  const script = read('railway/start-wordpress.sh');
  assert.match(script, /if \[ ! -f "\$ROOT\/index\.php" \]/);
  assert.match(script, /if \[ ! -f "\$ROOT\/wp-config\.php" \]/);
  const php = read('wp-content/themes/daniella-somers/inc/editable-home.php');
  const functions = read('wp-content/themes/daniella-somers/functions.php');
  assert.doesNotMatch(php + functions, /wp_delete_post\s*\(|daniella_repair_legacy_home_blocks/);
  assert.match(php, /require_once get_theme_file_path\('inc\/home-media\.php'\)/);
  assert.doesNotMatch(functions, /require_once[^;]*home-media-stability|require_once[^;]*block-editor-stability/);
});

test('one authoritative stylesheet is shared by the editor and public site', () => {
  const php = read('wp-content/themes/daniella-somers/functions.php');
  assert.match(php, /return array\('style\.css'\)/);
  assert.match(php, /add_editor_style\(daniella_theme_styles\(\)\)/);
  assert.equal(fs.readdirSync(path.join(root, 'wp-content/themes/daniella-somers')).filter(file => file.endsWith('.css')).join(','), 'style.css');
});

test('image processing and upload limits remain available', () => {
  const docker = read('Dockerfile');
  assert.match(docker, /extension_loaded\("gd"\)/);
  assert.match(docker, /function_exists\("imagewebp"\)/);
  assert.match(docker, /upload_max_filesize = 16M/);
  assert.match(docker, /post_max_size = 20M/);
});
