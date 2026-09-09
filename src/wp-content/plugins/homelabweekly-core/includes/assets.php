<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('body_class', 'homelabweekly_core_body_class');
add_action('wp_enqueue_scripts', 'homelabweekly_core_enqueue');

/**
 * @param string[] $classes
 * @return string[]
 */
function homelabweekly_core_body_class($classes)
{
    $classes[] = 'homelabweekly-core';
    return $classes;
}

function homelabweekly_core_enqueue()
{
    wp_enqueue_style(
        'homelabweekly-core',
        HOMELABWEEKLY_CORE_URL . 'assets/css/homelabweekly-core.css',
        array(),
        HOMELABWEEKLY_CORE_VERSION
    );

    wp_enqueue_script(
        'homelabweekly-core',
        HOMELABWEEKLY_CORE_URL . 'assets/js/homelabweekly-core.js',
        array(),
        HOMELABWEEKLY_CORE_VERSION,
        true
    );
}
