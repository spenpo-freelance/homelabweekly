<?php
/**
 * Plugin Name: Homelab Weekly Core
 * Description: Core shipping plugin for Homelab Weekly (newsletter/blog)
 * Version: 0.1.0
 * Author: Spenpo / Homelab Weekly
 * Text Domain: homelabweekly-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HOMELABWEEKLY_CORE_VERSION', '0.1.0');
define('HOMELABWEEKLY_CORE_FILE', __FILE__);
define('HOMELABWEEKLY_CORE_DIR', plugin_dir_path(__FILE__));
define('HOMELABWEEKLY_CORE_URL', plugin_dir_url(__FILE__));

require_once HOMELABWEEKLY_CORE_DIR . 'includes/assets.php';
require_once HOMELABWEEKLY_CORE_DIR . 'includes/shortcodes.php';
