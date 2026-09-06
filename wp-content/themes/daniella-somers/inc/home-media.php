<?php
/** Native, editable homepage media. No automatic changes to saved pages. */
if (!defined('ABSPATH')) { exit; }

function daniella_media_image($class) {
    return array(
        'blockName' => 'core/image',
        'attrs' => array('className' => $class, 'sizeSlug' => 'large', 'linkDestination' => 'none'),
        'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array(),
    );
}

function daniella_media_group($class, $children) {
    $block = parse_blocks('<!-- wp:group {"className":"' . $class . '","layout":{"type":"default"}} --><div class="wp-block-group ' . $class . '"></div><!-- /wp:group -->')[0];
    $block['innerBlocks'] = $children;
    $block['innerContent'] = array('<div class="wp-block-group ' . esc_attr($class) . '">');
    foreach ($children as $child) { $block['innerContent'][] = null; }
    $block['innerContent'][] = '</div>';
    return $block;
}

function daniella_media_has_class($block, $class) {
    return in_array($class, preg_split('/\s+/', $block['attrs']['className'] ?? ''), true);
}

function daniella_media_contains($blocks, $class) {
    foreach ($blocks as $block) {
        if (daniella_media_has_class($block, $class) || daniella_media_contains($block['innerBlocks'] ?? array(), $class)) { return true; }
    }
    return false;
}

function daniella_media_empty_legacy($block, $class) {
    if (!daniella_media_has_class($block, $class)) { return false; }
    $expected = $class === 'ds-room-slot' ? 'Add the counselling room photo here using an Image block.' : 'Add the supplied BACP Registered Member image here using an Image block.';
    if (stripos($block['innerHTML'] ?? '', '<img') !== false) { return false; }
    $children = $block['innerBlocks'] ?? array();
    return count($children) === 1 && $children[0]['blockName'] === 'core/paragraph' && trim(wp_strip_all_tags($children[0]['innerHTML'])) === $expected;
}

/** Insert a child before the closing wrapper, without rewriting existing children. */
function daniella_media_append(&$block, $child) {
    if (!isset($block['innerBlocks'], $block['innerContent'])) { return false; }
    $block['innerBlocks'][] = $child;
    $position = count($block['innerContent']) - 1;
    array_splice($block['innerContent'], $position, 0, array(null));
    return true;
}

function daniella_media_qualification_group() {
    return daniella_media_group('ds-qualification-media', array(
        daniella_media_image('ds-qualification-bacp ds-bacp-image'),
        daniella_media_image('ds-qualification-room ds-room-image'),
    ));
}

/** Only transform known empty placeholders. Existing images and custom blocks are untouched. */
function daniella_media_prepare($content) {
    $blocks = parse_blocks($content);
    $changed = false;
    $has_qualification = daniella_media_contains($blocks, 'ds-qualification-media');
    $has_room = daniella_media_contains($blocks, 'ds-room-image');
    $has_bacp = daniella_media_contains($blocks, 'ds-bacp-image');
    $walk = function (&$items) use (&$walk, &$changed, &$has_qualification, &$has_room, &$has_bacp) {
        foreach ($items as &$block) {
            if (daniella_media_has_class($block, 'ds-room-slot') && !$has_room && daniella_media_empty_legacy($block, 'ds-room-slot')) {
                $block = daniella_media_image('ds-room-image'); $has_room = true; $changed = true; continue;
            }
            if (daniella_media_has_class($block, 'ds-bacp-slot') && !$has_bacp && daniella_media_empty_legacy($block, 'ds-bacp-slot')) {
                $block = daniella_media_image('ds-bacp-image'); $has_bacp = true; $changed = true; continue;
            }
            if (!empty($block['innerBlocks'])) { $walk($block['innerBlocks']); }
            if (($block['attrs']['anchor'] ?? '') === 'qualifications' && !$has_qualification) {
                // Keep the original two-column design. Media belongs beneath the
                // heading/membership text in the first column, not outside the shell.
                foreach ($block['innerBlocks'] as &$shell) {
                    if (!daniella_media_has_class($shell, 'ds-split')) { continue; }
                    foreach ($shell['innerBlocks'] as &$column) {
                        if (daniella_media_append($column, daniella_media_qualification_group())) {
                            $has_qualification = true; $changed = true;
                        }
                        break;
                    }
                    unset($column);
                    break;
                }
                unset($shell);
            }
        }
        unset($block);
    };
    $walk($blocks);
    return $changed ? serialize_blocks($blocks) : $content;
}

add_action('init', function () {
    if (!function_exists('register_block_pattern')) { return; }
    register_block_pattern('daniella-somers/qualification-media', array(
        'title' => __('Qualifications: image placeholders', 'daniella-somers'),
        'description' => __('Replace the empty images with your genuine BACP badge and counselling room photo.', 'daniella-somers'),
        'categories' => array('media'),
        'content' => serialize_block(daniella_media_qualification_group()),
    ));
});

/** Explicit editor action, rather than silently overwriting the live homepage. */
add_action('admin_notices', function () {
    if (!current_user_can('edit_pages')) { return; }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $id = (int) get_option('page_on_front');
    if (!$screen || $screen->base !== 'post' || (int) ($_GET['post'] ?? 0) !== $id || !$id) { return; }
    if (isset($_GET['daniella_media'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Image placeholders updated. Existing images and page content were preserved.</p></div>';
        return;
    }
    $post = get_post($id);
    if (!$post || daniella_media_prepare($post->post_content) === $post->post_content) { return; }
    $url = wp_nonce_url(add_query_arg(array('action' => 'daniella_add_home_media', 'post_id' => $id), admin_url('admin-post.php')), 'daniella_add_home_media_' . $id);
    echo '<div class="notice notice-info"><p>New native Image blocks are available for the qualifications and room photos. Your existing images will not be replaced. <a href="' . esc_url($url) . '">Add missing image placeholders to Home</a>. Save any current edits first.</p></div>';
});

add_action('admin_post_daniella_add_home_media', function () {
    $id = absint($_GET['post_id'] ?? 0);
    if (!$id || !current_user_can('edit_post', $id)) { wp_die('Not permitted.'); }
    check_admin_referer('daniella_add_home_media_' . $id);
    if ($id !== (int) get_option('page_on_front')) { wp_die('This is not the current homepage.'); }
    $post = get_post($id);
    if (!$post || $post->post_type !== 'page') { wp_die('Page not found.'); }
    $updated = daniella_media_prepare($post->post_content);
    if ($updated !== $post->post_content) {
        wp_save_post_revision($id);
        $result = wp_update_post(array('ID' => $id, 'post_content' => $updated), true);
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message())); }
    }
    wp_safe_redirect(add_query_arg('daniella_media', 'updated', get_edit_post_link($id, 'raw')));
    exit;
});
