<?php
/**
 * Editable Home page bootstrap.
 *
 * The front-page template now renders normal page content, so the whole homepage can
 * be edited under Pages > Home in the block editor. The bootstrap only seeds content
 * when there is no existing page content; it never overwrites later editor changes.
 */
if (!defined('ABSPATH')) { exit; }

add_action('wp_enqueue_scripts', function () {
    $path = get_theme_file_path('editable-home.css');
    $version = file_exists($path) ? (string) filemtime($path) : wp_get_theme()->get('Version');
    wp_enqueue_style(
        'daniella-somers-editable-home',
        get_theme_file_uri('editable-home.css'),
        array('daniella-somers-exact'),
        $version
    );

    $responsive_path = get_theme_file_path('responsive-gutters.css');
    $responsive_version = file_exists($responsive_path) ? (string) filemtime($responsive_path) : $version;
    wp_enqueue_style(
        'daniella-somers-responsive-gutters',
        get_theme_file_uri('responsive-gutters.css'),
        array('daniella-somers-editable-home'),
        $responsive_version
    );
}, 20);

add_action('after_setup_theme', function () {
    add_editor_style(array('editable-home.css', 'responsive-gutters.css'));
});

function daniella_get_default_home_content() {
    $path = get_theme_file_path('content/home-page.html');
    if (!is_readable($path)) {
        return '';
    }

    // Only new/empty homepages receive these native image placeholders.
    return daniella_media_prepare((string) file_get_contents($path));
}

add_action('init', function () {
    $bootstrap_key = 'daniella_editable_home_bootstrap_20260906_v1';
    if (get_option($bootstrap_key)) {
        return;
    }

    $front_page_id = (int) get_option('page_on_front');
    $front_page = $front_page_id ? get_post($front_page_id) : null;

    if (!$front_page || $front_page->post_type !== 'page') {
        $front_page = get_page_by_path('home', OBJECT, 'page');
    }

    $default_content = daniella_get_default_home_content();

    if (!$front_page) {
        $front_page_id = wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Home',
            'post_name'    => 'home',
            'post_content' => $default_content,
        ), true);

        if (!is_wp_error($front_page_id)) {
            $front_page = get_post($front_page_id);
        }
    } elseif (trim((string) $front_page->post_content) === '' && $default_content !== '') {
        wp_update_post(array(
            'ID'           => $front_page->ID,
            'post_content' => $default_content,
        ));
        $front_page_id = (int) $front_page->ID;
    } else {
        $front_page_id = (int) $front_page->ID;
    }

    if ($front_page_id > 0) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $front_page_id);
    }

    // A saved Site Editor template overrides the version-controlled front-page.html.
    // Clear it once for this migration, then leave future Site Editor changes alone.
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

    update_option($bootstrap_key, gmdate('c'), false);
}, 120);

add_action('admin_notices', function () {
    if (!current_user_can('edit_pages')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'dashboard') {
        return;
    }

    $front_page_id = (int) get_option('page_on_front');
    if (!$front_page_id) {
        return;
    }

    $edit_url = get_edit_post_link($front_page_id, 'raw');
    if (!$edit_url) {
        return;
    }

    echo '<div class="notice notice-info is-dismissible"><p><strong>Daniella Somers homepage:</strong> the homepage is now editable in the visual block editor. <a href="' . esc_url($edit_url) . '">Edit Home page</a>.</p></div>';
});
