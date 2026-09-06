<?php
/**
 * Plugin Name: Daniella Editor Stability
 * Description: Loads the homepage Gutenberg normalisation and safe native-media migrations before init.
 */
if (!defined('ABSPATH')) { exit; }

add_action('plugins_loaded', function () {
    $theme_dir = WP_CONTENT_DIR . '/themes/daniella-somers';

    foreach (array(
        $theme_dir . '/inc/block-editor-stability.php',
        $theme_dir . '/inc/home-media-stability.php',
    ) as $file) {
        if (is_readable($file)) {
            require_once $file;
        }
    }
}, 20);
