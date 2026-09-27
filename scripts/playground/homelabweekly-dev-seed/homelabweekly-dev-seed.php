<?php
/**
 * Plugin Name: Homelab Weekly Dev Seed
 * Description: Seeds Home, About, Blog, Contact, sample posts, and the primary nav for the local Playground site. Not shipped to Hostinger.
 * Version: 1
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HOMELABWEEKLY_DEV_SEED_VERSION', '1');
define('HOMELABWEEKLY_DEV_SEED_OPTION', 'homelabweekly_dev_seeded');

add_action('init', 'homelabweekly_dev_seed_maybe_run', 50);
add_action('init', 'homelabweekly_dev_seed_handle_reseed', 45);

/**
 * Admin-only reseed: visit /?homelabweekly_reseed=1 while logged in.
 */
function homelabweekly_dev_seed_handle_reseed()
{
    if (!isset($_GET['homelabweekly_reseed'])) {
        return;
    }
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return;
    }
    delete_option(HOMELABWEEKLY_DEV_SEED_OPTION);
}

/**
 * @return void
 */
function homelabweekly_dev_seed_maybe_run()
{
    if (get_option(HOMELABWEEKLY_DEV_SEED_OPTION) === HOMELABWEEKLY_DEV_SEED_VERSION) {
        return;
    }
    if (function_exists('wp_installing') && wp_installing()) {
        return;
    }

    homelabweekly_dev_seed_run();
    update_option(HOMELABWEEKLY_DEV_SEED_OPTION, HOMELABWEEKLY_DEV_SEED_VERSION, false);
}

/**
 * @return void
 */
function homelabweekly_dev_seed_run()
{
    $user = get_user_by('login', 'admin');
    if ($user) {
        wp_set_password('admin', $user->ID);
    }

    homelabweekly_dev_seed_delete_defaults();

    $home_id = homelabweekly_dev_seed_page(
        'home',
        'Home',
        homelabweekly_dev_seed_home_content()
    );
    homelabweekly_dev_seed_page(
        'about',
        'About',
        homelabweekly_dev_seed_about_content()
    );
    homelabweekly_dev_seed_page(
        'contact',
        'Contact',
        homelabweekly_dev_seed_contact_content()
    );
    $blog_id = homelabweekly_dev_seed_page(
        'blog',
        'Blog',
        ''
    );

    homelabweekly_dev_seed_post(
        'victoriametrics-oci-integration',
        'VictoriaMetrics OCI Integration',
        homelabweekly_dev_seed_post_body(
            'Centralize homelab metrics in OCI with VictoriaMetrics.',
            'Point vmagent at a remote-write endpoint, scrape the usual node exporters, and hang Grafana off the same datasource. This seeded article is a stand-in so the local Blog and Home query loop have something to render.'
        ),
        'Centralize homelab metrics in OCI with VictoriaMetrics. vmagent, remote write, and Grafana.',
        '2026-03-11 10:00:00'
    );
    homelabweekly_dev_seed_post(
        'kubernetes-nas-persistent-volumes',
        'Kubernetes NAS Persistent Volumes',
        homelabweekly_dev_seed_post_body(
            'Move Kubernetes persistent volumes onto a NAS.',
            'K3s workloads last longer when the data lives on a network share instead of a local disk. This seeded article exists so the local query loops have a second post to show.'
        ),
        'Move Kubernetes persistent volumes to a NAS. K3s and PV migration notes.',
        '2026-03-11 11:00:00'
    );
    homelabweekly_dev_seed_post(
        'homelab-nas-migration-guide',
        'Homelab NAS Migration Guide',
        homelabweekly_dev_seed_post_body(
            'Migrate homelab services onto a NAS.',
            'Gitea, JupyterHub, and databases all benefit from a dedicated storage box. This seeded article is the third post the Home “latest 3” query needs.'
        ),
        'Migrate Gitea, JupyterHub, and databases to a NAS for storage and performance.',
        '2026-03-11 12:00:00'
    );
    homelabweekly_dev_seed_post(
        'homelab-business-prototype',
        'Homelab Business Prototype: From Hobby to MVP',
        homelabweekly_dev_seed_post_body(
            'Turn a homelab into a business prototype.',
            'The extra post keeps /blog/ from looking empty after the Home query shows only three items.'
        ),
        'Use homelab depth to prototype an MVP without a cloud bill.',
        '2026-03-10 09:00:00'
    );

    update_option('show_on_front', 'page');
    update_option('page_on_front', $home_id);
    update_option('page_for_posts', $blog_id);
    update_option('blogname', 'Homelab Weekly');
    update_option(
        'blogdescription',
        'A weekly newsletter of high-impact homelab projects — infrastructure, Kubernetes, storage, and ops that ships.'
    );
    update_option('permalink_structure', '/%postname%/');

    homelabweekly_dev_seed_navigation();

    if (function_exists('flush_rewrite_rules')) {
        flush_rewrite_rules(false);
    }
}

/**
 * Drop the default Hello world post and Sample Page so seeded content is the only content.
 *
 * @return void
 */
function homelabweekly_dev_seed_delete_defaults()
{
    $hello = get_page_by_path('hello-world', OBJECT, 'post');
    if ($hello instanceof WP_Post) {
        wp_delete_post($hello->ID, true);
    }

    $sample = get_page_by_path('sample-page');
    if ($sample instanceof WP_Post) {
        wp_delete_post($sample->ID, true);
    }
}

/**
 * @param string $slug
 * @param string $title
 * @param string $content
 * @return int
 */
function homelabweekly_dev_seed_page($slug, $title, $content)
{
    $existing = get_page_by_path($slug);
    $data = array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_name' => $slug,
        'post_title' => $title,
        'post_content' => $content,
        'post_author' => 1,
    );
    if ($existing instanceof WP_Post) {
        $data['ID'] = $existing->ID;
        return (int) wp_update_post($data, true);
    }
    return (int) wp_insert_post($data, true);
}

/**
 * @param string $slug
 * @param string $title
 * @param string $content
 * @param string $excerpt
 * @param string $date
 * @return int
 */
function homelabweekly_dev_seed_post($slug, $title, $content, $excerpt, $date)
{
    $existing = get_page_by_path($slug, OBJECT, 'post');
    $data = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_name' => $slug,
        'post_title' => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_author' => 1,
        'post_date' => $date,
        'post_date_gmt' => $date,
    );
    if ($existing instanceof WP_Post) {
        $data['ID'] = $existing->ID;
        return (int) wp_update_post($data, true);
    }
    return (int) wp_insert_post($data, true);
}

/**
 * Static front page: core-block hero plus a latest-3 posts query.
 *
 * @return string
 */
function homelabweekly_dev_seed_home_content()
{
    return <<<'CONTENT'
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide">
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Homelab Weekly</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size">A weekly newsletter of high-impact homelab projects — infrastructure, Kubernetes, storage, and ops that ships.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:heading {"align":"wide"} -->
<h2 class="wp-block-heading alignwide">Latest</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":1,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide"} -->
<div class="wp-block-query alignwide">
<!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:post-title {"isLink":true} /-->
<!-- wp:post-excerpt /-->
<!-- wp:post-date /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
CONTENT;
}

/**
 * @return string
 */
function homelabweekly_dev_seed_about_content()
{
    return <<<'CONTENT'
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">About</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>High-impact homelab projects — infrastructure, Kubernetes, storage, and ops that ships.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">A sandbox that ships</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Homelab Weekly is a newsletter about building a real local lab: virtualization, containers, storage, and the ops work that turns a pile of hardware into something you can learn from. The live site uses Twenty Twenty-Five; this Playground copy is a short stand-in so agents can exercise layout and <code>homelabweekly-core</code> without hitting production.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Treat the lab like a pre-cloud environment. Break things on purpose, restore from backup, and keep notes. That loop is the product.</p>
<!-- /wp:paragraph -->
CONTENT;
}

/**
 * Plain core-block Contact page. SureForms is installed if wordpress.org served it;
 * seeding a form is optional and skipped here.
 *
 * @return string
 */
function homelabweekly_dev_seed_contact_content()
{
    return <<<'CONTENT'
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Contact</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Questions about a lab write-up, a correction, or a topic you want covered? This local Playground page is a core-block stand-in. Production embeds a SureForms form; that form is not seeded here.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Use this page to check header, footer, and <code>homelabweekly-core</code> assets. Do not send mail from Playground.</p>
<!-- /wp:paragraph -->
CONTENT;
}

/**
 * @param string $lead
 * @param string $body
 * @return string
 */
function homelabweekly_dev_seed_post_body($lead, $body)
{
    $lead_esc = esc_html($lead);
    $body_esc = esc_html($body);

    return <<<CONTENT
<!-- wp:paragraph -->
<p>{$lead_esc}</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>{$body_esc}</p>
<!-- /wp:paragraph -->
CONTENT;
}

/**
 * Primary nav: Home / About / Blog / Contact.
 *
 * @return void
 */
function homelabweekly_dev_seed_navigation()
{
    $markup = <<<'HTML'
<!-- wp:navigation-link {"label":"Home","url":"/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"About","url":"/about/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Blog","url":"/blog/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Contact","url":"/contact/","kind":"custom"} /-->
HTML;

    $existing = get_posts(
        array(
            'post_type' => 'wp_navigation',
            'post_status' => 'publish',
            'title' => 'Primary',
            'numberposts' => 1,
        )
    );

    $data = array(
        'post_type' => 'wp_navigation',
        'post_status' => 'publish',
        'post_title' => 'Primary',
        'post_name' => 'primary',
        'post_content' => $markup,
    );

    if ($existing) {
        $data['ID'] = $existing[0]->ID;
        wp_update_post($data);
        $nav_id = (int) $existing[0]->ID;
    } else {
        $nav_id = (int) wp_insert_post($data);
    }

    if ($nav_id) {
        homelabweekly_dev_seed_bind_header_nav($nav_id);
    }
}

/**
 * Point Twenty Twenty-Five's header navigation at the seeded menu when possible.
 *
 * @param int $nav_id
 * @return void
 */
function homelabweekly_dev_seed_bind_header_nav($nav_id)
{
    if (!function_exists('get_block_templates') || !function_exists('wp_update_post')) {
        return;
    }

    $parts = get_block_templates(array('slug__in' => array('header')), 'wp_template_part');
    if (!is_array($parts)) {
        return;
    }

    foreach ($parts as $part) {
        if (empty($part->content) || empty($part->wp_id)) {
            continue;
        }
        $updated = preg_replace(
            '/<!-- wp:navigation(\s+\{[^}]*\})? \/\-->/',
            '<!-- wp:navigation {"ref":' . (int) $nav_id . '} /-->',
            $part->content,
            1
        );
        if (is_string($updated) && $updated !== $part->content) {
            wp_update_post(
                array(
                    'ID' => (int) $part->wp_id,
                    'post_content' => $updated,
                )
            );
        }
    }
}
