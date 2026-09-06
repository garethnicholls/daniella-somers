<?php
/** Daniella Somers block theme setup. */
if (!defined('ABSPATH')) { exit; }

add_action('wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('daniella-somers', get_stylesheet_uri(), array(), $version);
    wp_enqueue_style('daniella-somers-exact', get_template_directory_uri() . '/exact.css', array('daniella-somers'), $version);
});

add_action('after_setup_theme', function () {
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style(array('style.css', 'exact.css'));
});
