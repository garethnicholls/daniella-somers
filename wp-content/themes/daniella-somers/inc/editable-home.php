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
}, 20);

add_action('after_setup_theme', function () {
    add_editor_style('editable-home.css');
});

function daniella_get_default_home_content() {
    $path = get_theme_file_path('content/home-page.html');
    if (!is_readable($path)) {
        return '';
    }

    return (string) file_get_contents($path);
}

/**
 * Repair the first editable-home seed produced by PR #17.
 *
 * That version placed raw HTML shell divs inside core/group blocks. The public page
 * rendered, but Gutenberg treated parts of the page as malformed/invalid blocks.
 * These replacements change only those wrapper elements and keep edited copy, links,
 * images and shortcode content intact.
 */
function daniella_repair_legacy_home_blocks($content) {
    $content = (string) $content;

    $replacements = array(
        '<div class="wp-block-group ds-quote"><div class="ds-shell">' => '<div class="wp-block-group ds-quote">\n<!-- wp:group {"className":"ds-shell","layout":{"type":"default"}} --><div class="wp-block-group ds-shell">',
        '<div id="about" class="wp-block-group ds-section"><div class="ds-shell ds-split">' => '<div id="about" class="wp-block-group ds-section">\n<!-- wp:group {"className":"ds-shell ds-split","layout":{"type":"default"}} --><div class="wp-block-group ds-shell ds-split">',
        '<div id="practice" class="wp-block-group ds-section ds-soft"><div class="ds-shell">' => '<div id="practice" class="wp-block-group ds-section ds-soft">\n<!-- wp:group {"className":"ds-shell","layout":{"type":"default"}} --><div class="wp-block-group ds-shell">',
        '<div id="qualifications" class="wp-block-group ds-section ds-credentials"><div class="ds-shell ds-split">' => '<div id="qualifications" class="wp-block-group ds-section ds-credentials">\n<!-- wp:group {"className":"ds-shell ds-split","layout":{"type":"default"}} --><div class="wp-block-group ds-shell ds-split">',
        '<div id="fees" class="wp-block-group ds-section ds-fees"><div class="ds-shell ds-split">' => '<div id="fees" class="wp-block-group ds-section ds-fees">\n<!-- wp:group {"className":"ds-shell ds-split","layout":{"type":"default"}} --><div class="wp-block-group ds-shell ds-split">',
        '<div id="contact" class="wp-block-group ds-contact ds-contact-editor"><div class="ds-shell">' => '<div id="contact" class="wp-block-group ds-contact ds-contact-editor">\n<!-- wp:group {"className":"ds-shell","layout":{"type":"default"}} --><div class="wp-block-group ds-shell">',
    );

    $had_legacy_shell = false;
    foreach ($replacements as $legacy => $valid) {
        if (strpos($content, $legacy) !== false) {
            $had_legacy_shell = true;
            $content = str_replace($legacy, $valid, $content);
        }
    }

    if ($had_legacy_shell) {
        $content = str_replace(
            '</div></div><!-- /wp:group -->',
            '</div><!-- /wp:group -->\n</div><!-- /wp:group -->',
            $content
        );
    }

    return $content;
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

/** Repair already-seeded PR #17 Home content without overwriting user edits. */
add_action('init', function () {
    $repair_key = 'daniella_editable_home_block_repair_20260906_v2';
    if (get_option($repair_key)) {
        return;
    }

    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id <= 0) {
        return;
    }

    $front_page = get_post($front_page_id);
    if (!$front_page || $front_page->post_type !== 'page') {
        return;
    }

    $original = (string) $front_page->post_content;
    $repaired = daniella_repair_legacy_home_blocks($original);

    if ($repaired !== $original) {
        wp_update_post(array(
            'ID'           => $front_page_id,
            'post_content' => $repaired,
        ));
    }

    update_option($repair_key, gmdate('c'), false);
}, 130);

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

    echo '<div class="notice notice-info is-dismissible"><p><strong>Daniella Somers homepage:</strong> the homepage is editable in the visual block editor. <a href="' . esc_url($edit_url) . '">Edit Home page</a>.</p></div>';
});
