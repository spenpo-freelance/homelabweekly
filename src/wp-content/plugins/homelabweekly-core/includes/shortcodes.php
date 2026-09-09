<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('homelabweekly_site_name', 'homelabweekly_core_site_name_shortcode');

/**
 * @return string
 */
function homelabweekly_core_site_name_shortcode()
{
    return esc_html('Homelab Weekly');
}
