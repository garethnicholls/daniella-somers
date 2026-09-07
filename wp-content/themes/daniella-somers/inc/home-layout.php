<?php
/** Native Home layout compatibility. No writes to saved content. */
if (!defined('ABSPATH')) { exit; }

/** Only supply legacy layout defaults when a block has no explicit layout. */
function daniella_home_layout_defaults($block) {
    if (!is_front_page() || ($block['blockName'] ?? '') !== 'core/group') { return $block; }
    $attrs = $block['attrs'] ?? array();
    if (isset($attrs['layout'])) { return $block; }
    $classes = preg_split('/\s+/', $attrs['className'] ?? '');
    $defaults = array(
        'ds-hero-grid' => array('type' => 'grid', 'columnCount' => 2),
        'ds-split' => array('type' => 'grid', 'columnCount' => 2),
        'ds-card-grid' => array('type' => 'grid', 'columnCount' => 3),
        'ds-contact-layout' => array('type' => 'grid', 'columnCount' => 3),
    );
    foreach ($defaults as $class => $layout) {
        if (in_array($class, $classes, true)) {
            $block['attrs']['layout'] = $layout;
            break;
        }
    }
    return $block;
}
