<?php
/**
 * Keep the editable homepage valid in Gutenberg.
 *
 * The original homepage markup used unregistered raw <div class="ds-shell"> wrappers
 * inside core Group blocks. They render fine in a browser, but Gutenberg compares the
 * saved HTML with the block serializer and can mark neighbouring blocks (especially
 * Image blocks) as invalid/corrupted after an edit.
 *
 * This migration converts only those known structural wrappers into real core Group
 * blocks. It preserves every existing child block verbatim, including user-selected
 * Media Library image URLs/IDs and contact content.
 */
if (!defined('ABSPATH')) { exit; }

function daniella_normalise_home_shell_blocks($content) {
    if (!is_string($content) || $content === '') {
        return $content;
    }

    $replacements = array(
        '<div class="ds-shell ds-hero-grid">' => '<!-- wp:group {"className":"ds-shell ds-hero-grid","layout":{"type":"default"}} --><div class="wp-block-group ds-shell ds-hero-grid">',
        '<div class="ds-shell ds-split">'     => '<!-- wp:group {"className":"ds-shell ds-split","layout":{"type":"default"}} --><div class="wp-block-group ds-shell ds-split">',
        '<div class="ds-shell">'              => '<!-- wp:group {"className":"ds-shell","layout":{"type":"default"}} --><div class="wp-block-group ds-shell">',
    );

    foreach ($replacements as $raw => $block_open) {
        $content = str_replace($raw, $block_open, $content);
    }

    /*
     * Each legacy shell wrapper was immediately closed before its parent Group's
     * closing marker. Convert that raw close into the matching core Group close.
     * Run repeatedly because several sections share the same pattern.
     */
    $content = preg_replace(
        '#</div></div>\s*<!-- /wp:group -->#',
        '</div><!-- /wp:group --></div><!-- /wp:group -->',
        $content
    );

    return $content;
}

add_action('init', function () {
    $migration_key = 'daniella_home_valid_blocks_20260906_v1';
    if (get_option($migration_key)) {
        return;
    }

    $front_page_id = (int) get_option('page_on_front');
    $page = $front_page_id ? get_post($front_page_id) : null;

    if (!$page || $page->post_type !== 'page') {
        update_option($migration_key, 'no-front-page', false);
        return;
    }

    $before = (string) $page->post_content;
    $after  = daniella_normalise_home_shell_blocks($before);

    if ($after !== $before) {
        /* Prevent this migration itself from triggering unrelated save handlers twice. */
        remove_action('save_post', 'daniella_home_block_migration_save_guard', 10);
        wp_update_post(array(
            'ID'           => $page->ID,
            'post_content' => wp_slash($after),
        ));
    }

    update_option($migration_key, gmdate('c'), false);
}, 130);
