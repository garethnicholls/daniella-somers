<?php
/** Daniella Somers block theme setup. */
if (!defined('ABSPATH')) { exit; }

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'daniella-somers',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );
});

add_action('after_setup_theme', function () {
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
});
