<?php
/**
 * Safe homepage media migration.
 *
 * The public design stays structurally identical to the approved reference, but the
 * editable page uses real Gutenberg Image blocks. This avoids the old failure mode
 * where an uploaded <img> was inserted inside a Paragraph/placeholder block and the
 * editor then reported neighbouring blocks as invalid/corrupted.
 *
 * This migration is intentionally surgical: it does not replace headings, body copy,
 * buttons, forms or user-selected images. It only converts the two known media slots,
 * repairs the known legacy portrait placeholder, and protects the top-level layout.
 */
if (!defined('ABSPATH')) { exit; }

function daniella_home_native_image_block($src, $alt, $class_name) {
    $src = esc_url($src);
    $alt = esc_attr($alt);
    $class_name = sanitize_html_class($class_name);

    return '<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"' . $class_name . '","lock":{"move":true,"remove":true}} -->'
        . '<figure class="wp-block-image size-large ' . $class_name . '"><img src="' . $src . '" alt="' . $alt . '"/></figure>'
        . '<!-- /wp:image -->';
}

function daniella_stabilise_home_media($content) {
    if (!is_string($content) || $content === '') {
        return $content;
    }

    /* Keep the overall design fixed while still allowing text/media replacement. */
    $content = str_replace(
        '<!-- wp:group {"tagName":"main","layout":{"type":"default"},"anchor":"top"} -->',
        '<!-- wp:group {"tagName":"main","layout":{"type":"default"},"anchor":"top","templateLock":"contentOnly"} -->',
        $content
    );

    /*
     * These are the two supplied images already present in this site's Media Library.
     * They are site-owned uploads, not external resources, and remain replaceable via
     * the normal Image block Replace control in Gutenberg.
     */
    $room = daniella_home_native_image_block(
        home_url('/wp-content/uploads/2026/09/6C204151-4A47-472C-9003-7B1592FD30CB.png'),
        'Daniella Somers counselling room',
        'ds-room-image'
    );
    $bacp = daniella_home_native_image_block(
        home_url('/wp-content/uploads/2026/09/0994C14B-C81D-41F2-ABFD-CF174AC4970D.png'),
        'BACP Registered Member MBACP',
        'ds-bacp-image'
    );

    $content = preg_replace(
        '#<!-- wp:group \{"className":"ds-media-slot ds-room-slot".*?<!-- /wp:group -->#s',
        $room,
        $content,
        1
    );
    $content = preg_replace(
        '#<!-- wp:group \{"className":"ds-media-slot ds-bacp-slot".*?<!-- /wp:group -->#s',
        $bacp,
        $content,
        1
    );

    /* Repair the original malformed portrait placeholder without discarding its image. */
    if (strpos($content, 'ds-portrait-placeholder') !== false) {
        $portrait_src = '';
        if (preg_match('#ds-portrait-placeholder.*?<img[^>]+src=["\']([^"\']+)["\']#s', $content, $match)) {
            $portrait_src = $match[1];
        }
        if (!$portrait_src) {
            $portrait_src = get_theme_file_uri('assets/daniella-hero.jpg');
        }

        $portrait = daniella_home_native_image_block(
            $portrait_src,
            'Daniella Somers',
            'ds-hero-editable-image'
        );

        $content = preg_replace(
            '#<!-- wp:group \{"className":"ds-portrait-placeholder".*?<!-- /wp:group -->#s',
            $portrait,
            $content,
            1
        );
    }

    return $content;
}

add_action('init', function () {
    $migration_key = 'daniella_home_native_media_20260906_v2';
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
    $after  = daniella_stabilise_home_media($before);

    if ($after !== $before) {
        wp_update_post(array(
            'ID'           => $page->ID,
            'post_content' => wp_slash($after),
        ));
    }

    update_option($migration_key, gmdate('c'), false);
}, 140);
