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

/**
 * One-time production bootstrap.
 *
 * Earlier Site Editor changes created a database copy of the Front Page template.
 * That copy overrides the version-controlled theme template and caused the stale
 * contact placeholder / broken portrait block to survive deployments. Remove only
 * that customized Front Page template once, then leave future editor changes alone.
 */
add_action('init', function () {
    $reset_key = 'daniella_front_page_reset_20260906_v1';

    if (get_option($reset_key)) {
        return;
    }

    $templates = get_posts(array(
        'post_type'      => 'wp_template',
        'post_status'    => array('publish', 'draft'),
        'name'           => 'front-page',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    ));

    foreach ($templates as $template) {
        wp_delete_post($template->ID, true);
    }

    update_option($reset_key, gmdate('c'), false);
}, 99);

/**
 * Ensure the small plugin set bundled in the Railway image is active.
 * This is intentionally limited to the two plugins used by this site.
 */
add_action('admin_init', function () {
    if (!current_user_can('activate_plugins')) {
        return;
    }

    if (!function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    foreach (array(
        'contact-form-7/wp-contact-form-7.php',
        'fluent-smtp/fluent-smtp.php',
    ) as $plugin) {
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin) && !is_plugin_active($plugin)) {
            activate_plugin($plugin, '', false, true);
        }
    }
});

/**
 * Render the first published Contact Form 7 form without hard-coding a site-specific ID.
 * This keeps the block template portable between Railway deployments and local/staging sites.
 */
add_shortcode('daniella_contact_form', function () {
    if (!shortcode_exists('contact-form-7')) {
        return current_user_can('manage_options')
            ? '<p class="ds-contact-note">Contact Form 7 is not active yet.</p>'
            : '';
    }

    $forms = get_posts(array(
        'post_type'      => 'wpcf7_contact_form',
        'post_status'    => 'publish',
        'numberposts'    => 1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ));

    if (!$forms) {
        return current_user_can('manage_options')
            ? '<p class="ds-contact-note">Create a Contact Form 7 form and it will appear here automatically.</p>'
            : '';
    }

    $form = $forms[0];

    return do_shortcode(sprintf(
        '[contact-form-7 id="%d" title="%s"]',
        absint($form->ID),
        esc_attr($form->post_title)
    ));
});

/**
 * IndexNow support.
 *
 * The key is intentionally public: IndexNow requires it to be retrievable from the
 * website so search engines can verify submissions. The endpoint below serves the
 * key at the site root without needing to write into the Railway container.
 */
define('DANIELLA_INDEXNOW_KEY', '7b6e3a8f4e5c4fda8f28d64ce731b1ab');

add_action('template_redirect', function () {
    $requested_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $key_path = DANIELLA_INDEXNOW_KEY . '.txt';

    if ($requested_path !== $key_path) {
        return;
    }

    nocache_headers();
    header('Content-Type: text/plain; charset=UTF-8');
    echo DANIELLA_INDEXNOW_KEY;
    exit;
});

function daniella_indexnow_submit($urls) {
    $urls = array_values(array_unique(array_filter(array_map('esc_url_raw', (array) $urls))));

    if (!$urls) {
        return false;
    }

    $home = home_url('/');
    $host = wp_parse_url($home, PHP_URL_HOST);

    if (!$host) {
        return false;
    }

    $response = wp_remote_post('https://api.indexnow.org/indexnow', array(
        'timeout' => 10,
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body'    => wp_json_encode(array(
            'host'        => $host,
            'key'         => DANIELLA_INDEXNOW_KEY,
            'keyLocation' => home_url('/' . DANIELLA_INDEXNOW_KEY . '.txt'),
            'urlList'     => $urls,
        )),
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    return in_array($code, array(200, 202), true);
}

/** Submit the live homepage once after this deployment reaches production. */
add_action('wp_loaded', function () {
    $option = 'daniella_indexnow_initial_submit_v1';

    if (get_option($option)) {
        return;
    }

    if (daniella_indexnow_submit(array(home_url('/')))) {
        update_option($option, gmdate('c'), false);
    }
}, 20);

/** Re-submit public content whenever it is published or updated. */
add_action('save_post', function ($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (!$post || $post->post_status !== 'publish') {
        return;
    }

    if (!is_post_type_viewable($post->post_type)) {
        return;
    }

    $url = get_permalink($post_id);
    if ($url) {
        daniella_indexnow_submit(array($url));
    }
}, 20, 3);

require_once get_theme_file_path('inc/editable-home.php');
