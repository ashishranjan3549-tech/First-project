<?php
/**
 * Plugin Name: FlipX Engine
 * Description: Real-money card betting game engine for WordPress and WooCommerce.
 * Version: 1.0.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FLIPX_ENGINE_VERSION', '1.0.0');
define('FLIPX_ENGINE_PATH', plugin_dir_path(__FILE__));
define('FLIPX_ENGINE_URL', plugin_dir_url(__FILE__));

require_once FLIPX_ENGINE_PATH . 'includes/db-schema.php';
require_once FLIPX_ENGINE_PATH . 'includes/wallet.php';
require_once FLIPX_ENGINE_PATH . 'includes/betting.php';
require_once FLIPX_ENGINE_PATH . 'includes/round-engine.php';
require_once FLIPX_ENGINE_PATH . 'includes/withdrawals.php';
require_once FLIPX_ENGINE_PATH . 'includes/admin-panel.php';
require_once FLIPX_ENGINE_PATH . 'includes/woocommerce-hook.php';
require_once FLIPX_ENGINE_PATH . 'includes/ajax-handlers.php';

register_activation_hook(__FILE__, 'flipx_engine_activate');
function flipx_engine_activate(): void
{
    flipx_engine_create_tables();
    flipx_engine_seed_defaults();
    flipx_round_ensure_active();
    if (!wp_next_scheduled('flipx_round_tick_event')) {
        wp_schedule_event(time(), 'every_minute', 'flipx_round_tick_event');
    }
}

add_filter('cron_schedules', function (array $schedules): array {
    $schedules['every_minute'] = ['interval' => 60, 'display' => __('Every Minute', 'flipx-engine')];
    return $schedules;
});

register_deactivation_hook(__FILE__, function (): void {
    wp_clear_scheduled_hook('flipx_round_tick_event');
});

add_action('flipx_round_tick_event', 'flipx_round_tick_handler');
add_action('init', 'flipx_round_tick_handler');
