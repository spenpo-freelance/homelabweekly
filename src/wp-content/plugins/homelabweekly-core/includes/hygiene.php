<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One-shot: drop Hostinger/Spectra plugins that remain in runtime
 * active_plugins (object cache) after the DB row was already cleaned.
 * Gated by homelabweekly_hygiene_v1_done — safe to delete this file after.
 */
add_action('init', 'homelabweekly_core_hygiene_v1_run', 20);

/**
 * @return string[] Plugin basenames to force-deactivate.
 */
function homelabweekly_core_hygiene_v1_targets()
{
    return array(
        'hostinger-ai-assistant/hostinger-ai-assistant.php',
        'hostinger-reach/hostinger-reach.php',
        'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php',
    );
}

/**
 * Must-keep plugin directory slugs. Never removed by this one-shot.
 *
 * @return string[]
 */
function homelabweekly_core_hygiene_v1_protected_slugs()
{
    return array(
        'homelabweekly-core',
        'ai-engine',
        'akismet',
        'all-in-one-seo-pack',
        'google-analytics-for-wordpress',
        'google-site-kit',
        'litespeed-cache',
        'sureforms',
        'wpforms-lite',
    );
}

/**
 * @param mixed $active Raw active_plugins option value.
 * @return string[]
 */
function homelabweekly_core_hygiene_v1_filter_active_plugins($active)
{
    if (!is_array($active)) {
        return array();
    }

    $remove = array_fill_keys(homelabweekly_core_hygiene_v1_targets(), true);
    $protected = array_fill_keys(homelabweekly_core_hygiene_v1_protected_slugs(), true);
    $filtered = array();

    foreach ($active as $plugin) {
        if (!is_string($plugin) || $plugin === '') {
            continue;
        }

        $slug = dirname($plugin);
        if (isset($protected[$slug])) {
            $filtered[] = $plugin;
            continue;
        }

        if (isset($remove[$plugin])) {
            continue;
        }

        $filtered[] = $plugin;
    }

    return array_values($filtered);
}

function homelabweekly_core_hygiene_v1_run()
{
    if (get_option('homelabweekly_hygiene_v1_done')) {
        return;
    }

    $targets = homelabweekly_core_hygiene_v1_targets();
    $active = get_option('active_plugins', array());
    $filtered = homelabweekly_core_hygiene_v1_filter_active_plugins($active);

    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Silent: do not run deactivation hooks or delete plugin files.
    deactivate_plugins($targets, true);

    // Never persist an empty plugin list (corrupt/missing cache). Keepers stay.
    if ($filtered !== array()) {
        update_option('active_plugins', $filtered);
        if (function_exists('wp_cache_set')) {
            wp_cache_set('active_plugins', $filtered, 'options');
        }
    }

    if (function_exists('wp_cache_delete')) {
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('notoptions', 'options');
    }

    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

    homelabweekly_core_hygiene_v1_purge_litespeed();

    if ($filtered !== array() && function_exists('wp_cache_set')) {
        wp_cache_set('active_plugins', $filtered, 'options');
    }

    update_option('homelabweekly_hygiene_v1_done', time());
}

function homelabweekly_core_hygiene_v1_purge_litespeed()
{
    if (!defined('LITESPEED_PURGE_SILENT')) {
        define('LITESPEED_PURGE_SILENT', true);
    }

    try {
        if (function_exists('has_action') && has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
            return;
        }

        if (class_exists('\LiteSpeed\Purge') && is_callable(array('\LiteSpeed\Purge', 'purge_all'))) {
            \LiteSpeed\Purge::purge_all();
        }
    } catch (Throwable $e) {
        // Best-effort: missing or half-loaded LiteSpeed must not block the gate.
    }
}
