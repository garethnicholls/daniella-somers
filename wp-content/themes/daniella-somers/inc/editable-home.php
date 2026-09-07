<?php
/** Editable homepage bootstrap. Existing editor content is authoritative. */
if (!defined('ABSPATH')) { exit; }

function daniella_get_default_home_content() {
    $path = get_theme_file_path('content/home-page.html');
    return is_readable($path) ? (string) file_get_contents($path) : '';
}

/**
 * Seed only a genuinely new or empty Home page. Never repair, replace, or
 * normalise an existing page on a public request or during a deployment.
 * Existing page content, attachments and Site Editor templates belong to the
 * WordPress database, not the theme release.
 */
add_action('init', function () {
    $bootstrap_key = 'daniella_editable_home_bootstrap_20260906_v1';
    if (get_option($bootstrap_key)) { return; }

    $front_page_id = (int) get_option('page_on_front');
    $front_page = $front_page_id ? get_post($front_page_id) : null;
    if (!$front_page || $front_page->post_type !== 'page') {
        $front_page = get_page_by_path('home', OBJECT, 'page');
    }

    $default_content = daniella_get_default_home_content();
    if (!$front_page) {
        if ($default_content === '') { return; }
        $front_page_id = wp_insert_post(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Home',
            'post_name' => 'home',
            'post_content' => wp_slash($default_content),
        ), true);
        if (is_wp_error($front_page_id)) { return; }
    } elseif (trim((string) $front_page->post_content) === '' && $default_content !== '') {
        $result = wp_update_post(array(
            'ID' => $front_page->ID,
            'post_content' => wp_slash($default_content),
        ), true);
        if (is_wp_error($result)) { return; }
        $front_page_id = (int) $front_page->ID;
    } else {
        $front_page_id = (int) $front_page->ID;
    }

    if ($front_page_id > 0) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $front_page_id);
        update_option($bootstrap_key, gmdate('c'), false);
    }
}, 120);
