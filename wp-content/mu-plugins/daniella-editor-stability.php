<?php
/**
 * Plugin Name: Daniella Editor Stability
 * Description: Preserves existing WordPress content and provides opt-in native media tools.
 */
if (!defined('ABSPATH')) { exit; }

// The old regex migrations could rewrite saved block markup on a public request.
// They are deliberately disabled. Existing content and Media Library attachments
// must not be changed by a theme deployment. Keep only the explicit editor tools.
add_action('plugins_loaded', function () {
    $file = WP_CONTENT_DIR . '/themes/daniella-somers/inc/home-media.php';
    if (is_readable($file)) {
        require_once $file;
    }
}, 20);
