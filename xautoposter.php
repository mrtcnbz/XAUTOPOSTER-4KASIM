<?php
/**
 * Plugin Name: XAutoPoster
 * Description: WordPress içeriklerinizi otomatik olarak X (Twitter) hesabınızda paylaşın
 * Version: 1.0.0
 * Author: Murat Canbaz
 * Text Domain: xautoposter
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('XAUTOPOSTER_FILE', __FILE__);
define('XAUTOPOSTER_PATH', plugin_dir_path(__FILE__));
define('XAUTOPOSTER_URL', plugin_dir_url(__FILE__));

// Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Initialize the plugin
function xautoposter_init() {
    if (!class_exists('XAutoPoster\\Plugin')) {
        require_once XAUTOPOSTER_PATH . 'src/Plugin.php';
    }
    return \XAutoPoster\Plugin::getInstance();
}

// Start the plugin
add_action('plugins_loaded', 'xautoposter_init');