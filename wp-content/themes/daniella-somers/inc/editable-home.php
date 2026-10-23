<?php
/**
 * Native homepage editing.
 *
 * The saved WordPress page is authoritative. Theme deployments must not create,
 * replace, repair or reset its content or the Site Editor's saved templates.
 * Keep the existing optional native media tools; they never run automatically.
 */
if (!defined('ABSPATH')) { exit; }
require_once get_theme_file_path('inc/home-media.php');

/**
 * Expose the approved page as a native block pattern, so it can be inserted or
 * repaired from Pages > Front Page without editing a template or Customizer.
 */
add_action('init', function () {
    $source = get_theme_file_path('content/home-page.html');
    if (!is_readable($source) || !function_exists('register_block_pattern')) { return; }
    register_block_pattern('daniella-somers/front-page', array(
        'title'       => __('Daniella Somers front page', 'daniella-somers'),
        'description' => __('The approved editable front-page composition.', 'daniella-somers'),
        'categories'  => array('featured'),
        'blockTypes'  => array('core/post-content'),
        'content'     => (string) file_get_contents($source),
    ));
});
