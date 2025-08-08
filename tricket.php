<?php

/*
Plugin Name: Tricket
Plugin URI: https://github.com/tritagonist-io/tricket_wordpress
Description: Integrates your WordPress site with Tricket, fetching and displaying production data.
Version: 1.0.0
Author: Tritagonist
Author URI: https://tricket.net
License: Proprietary – see LICENSE.txt for details.
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

define('TRICKET_VERSION', '1.0.0');
define('TRICKET_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TRICKET_PLUGIN_URL', plugin_dir_url(__FILE__));

function activate_plugin_name()
{
    add_option('tricket_version', TRICKET_VERSION);

    tricket_rewrite_rules();
    flush_rewrite_rules();
}

function deactivate_plugin_name()
{
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_plugin_name');
register_deactivation_hook(__FILE__, 'deactivate_plugin_name');

function tricket_rewrite_rules()
{
    $options = get_option('tricket_options');
    $productions_slug = $options['tricket_productions_slug'];
    add_rewrite_tag('%production_title%', '([^&]+)');
    add_rewrite_rule('^' . $productions_slug . '/([^/]+)/?$', 'index.php?production_title=$matches[1]', 'top');
}

add_action('init', 'tricket_rewrite_rules');

function query_vars($vars)
{
    $vars[] = 'production_title';
    return $vars;
}

add_filter('query_vars', 'query_vars');

function template_redirect(): void
{
    $production = get_query_var('production_title');
    if ($production) {
        $theme_template = locate_template('single-production.php');
        if ($theme_template) {
            include($theme_template);
        } else {
            include_style();
            include(TRICKET_PLUGIN_DIR . 'templates/single-production.php');
        }
        exit;
    }
}

add_action('template_redirect', 'template_redirect');

function productions_shortcode()
{
    include_style();
    $tricket = new Tricket();
    $productions = $tricket->get_productions();
    if (!$productions) {
        return '<p>No productions found.</p>';
    }

    ob_start();
    include TRICKET_PLUGIN_DIR . 'templates/productions-list.php';
    return ob_get_clean();
}
add_shortcode('tricket_productions', 'productions_shortcode');

function schedule_shortcode()
{
    include_style();
    $tricket = new Tricket();
    $schedule = $tricket->get_schedule();
    
    if (!$schedule->has_screenings()) {
        return '<p>No screenings scheduled.</p>';
    }

    // Get productions for the template
    $productions = $tricket->get_productions();

    remove_filter('the_content', 'wpautop');
    ob_start();
    include TRICKET_PLUGIN_DIR . 'templates/schedule.php';

    return ob_get_clean();
}
add_shortcode('tricket_schedule', 'schedule_shortcode');

function include_style()
{
    wp_enqueue_style('tricket-styles', TRICKET_PLUGIN_URL . 'assets/css/tricket-styles.css', array(), TRICKET_VERSION);
}
require_once TRICKET_PLUGIN_DIR . 'includes/tricket-main.php';
require_once TRICKET_PLUGIN_DIR . 'admin/tricket-settings.php';
