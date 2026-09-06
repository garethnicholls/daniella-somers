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
