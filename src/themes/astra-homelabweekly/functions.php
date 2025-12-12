<?php
/**
 * Astra Homelab Weekly Newsletter Theme Functions
 * 
 * This file contains the functions for the Astra Homelab Weekly Newsletter theme.
 * 
 * @package Astra_Homelab_Weekly_Newsletter
 * @subpackage Functions
 * @since 1.0.0
 * @version 1.0.0
 */

 add_action('wp_enqueue_scripts', function() {
    // Enqueue parent theme styles first
    wp_enqueue_style(
        'astra-style',
        get_template_directory_uri() . '/style.css'
    );
    
    // Enqueue child theme's main style.css
    wp_enqueue_style(
        'astra-homelabweekly-style',
        get_stylesheet_uri(),
        ['astra-style']
    );
});