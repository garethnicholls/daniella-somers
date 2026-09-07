<?php
/** Daniella Somers block theme setup. */
if (!defined('ABSPATH')) { exit; }

/** One ordered stylesheet pipeline for the public site and Gutenberg. */
function daniella_theme_styles() {
    return array('style.css', 'exact.css', 'editable-home.css', 'responsive-gutters.css', 'home-media.css');
}

add_action('wp_enqueue_scripts', function () {
    $previous = array();
    foreach (daniella_theme_styles() as $file) {
        $handle = 'daniella-somers-' . sanitize_title(basename($file, '.css'));
        $path = get_theme_file_path($file);
        wp_enqueue_style(
            $handle,
            $file === 'style.css' ? get_stylesheet_uri() : get_theme_file_uri($file),
            $previous,
            is_readable($path) ? (string) filemtime($path) : wp_get_theme()->get('Version')
        );
        $previous = array($handle);
    }
}, 20);

add_action('after_setup_theme', function () {
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style(daniella_theme_styles());
});

/** Keep the two existing production plugins available. */
add_action('admin_init', function () {
    if (!current_user_can('activate_plugins')) { return; }
    if (!function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    foreach (array('contact-form-7/wp-contact-form-7.php', 'fluent-smtp/fluent-smtp.php') as $plugin) {
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin) && !is_plugin_active($plugin)) {
            activate_plugin($plugin, '', false, true);
        }
    }
});

/** Render the first published Contact Form 7 form without a site-specific ID. */
add_shortcode('daniella_contact_form', function () {
    if (!shortcode_exists('contact-form-7')) {
        return current_user_can('manage_options')
            ? '<p class="ds-contact-note">Contact Form 7 is not active yet.</p>' : '';
    }
    $forms = get_posts(array(
        'post_type' => 'wpcf7_contact_form',
        'post_status' => 'publish',
        'numberposts' => 1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));
    if (!$forms) {
        return current_user_can('manage_options')
            ? '<p class="ds-contact-note">Create a Contact Form 7 form and it will appear here automatically.</p>' : '';
    }
    $form = $forms[0];
    return do_shortcode(sprintf(
        '[contact-form-7 id="%d" title="%s"]',
        absint($form->ID), esc_attr($form->post_title)
    ));
});

/** IndexNow uses a public verification key, not a private credential. */
define('DANIELLA_INDEXNOW_KEY', '7b6e3a8f4e5c4fda8f28d64ce731b1ab');

add_action('template_redirect', function () {
    $requested_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if ($requested_path !== DANIELLA_INDEXNOW_KEY . '.txt') { return; }
    nocache_headers();
    header('Content-Type: text/plain; charset=UTF-8');
    echo DANIELLA_INDEXNOW_KEY;
    exit;
});

function daniella_indexnow_submit($urls) {
    $urls = array_values(array_unique(array_filter(array_map('esc_url_raw', (array) $urls))));
    if (!$urls) { return false; }
    $home = home_url('/');
    $host = wp_parse_url($home, PHP_URL_HOST);
    if (!$host) { return false; }
    $response = wp_remote_post('https://api.indexnow.org/indexnow', array(
        'timeout' => 10,
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => wp_json_encode(array(
            'host' => $host,
            'key' => DANIELLA_INDEXNOW_KEY,
            'keyLocation' => home_url('/' . DANIELLA_INDEXNOW_KEY . '.txt'),
            'urlList' => $urls,
        )),
    ));
    if (is_wp_error($response)) { return false; }
    return in_array((int) wp_remote_retrieve_response_code($response), array(200, 202), true);
}

add_action('wp_loaded', function () {
    $option = 'daniella_indexnow_initial_submit_v1';
    if (get_option($option)) { return; }
    if (daniella_indexnow_submit(array(home_url('/')))) {
        update_option($option, gmdate('c'), false);
    }
}, 20);

add_action('save_post', function ($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) { return; }
    if (!$post || $post->post_status !== 'publish') { return; }
    if (!is_post_type_viewable($post->post_type)) { return; }
    $url = get_permalink($post_id);
    if ($url) { daniella_indexnow_submit(array($url)); }
}, 20, 3);

require_once get_theme_file_path('inc/editable-home.php');
