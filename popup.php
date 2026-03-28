<?php
/**
 * Plugin Name: No Bloat Popups
 * Description: Leichtgewichtiges Popup-Plugin mit JS-basierter Cookie-Logik für Cache-Kompatibilität.
 * Version: 1.0.0
 * Author: Thomas Pondelek
 * Text Domain: no-bloat-popups
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NBP_VERSION', '1.0.0');
define('NBP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NBP_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once NBP_PLUGIN_DIR . 'includes/cpt.php';
require_once NBP_PLUGIN_DIR . 'includes/metaboxes.php';
require_once NBP_PLUGIN_DIR . 'includes/frontend.php';
