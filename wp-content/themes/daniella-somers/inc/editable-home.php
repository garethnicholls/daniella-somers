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
