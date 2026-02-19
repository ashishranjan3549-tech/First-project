<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    register_nav_menus([
        'primary' => __('Primary Menu', 'flipx-theme'),
    ]);
}
add_action('after_setup_theme', 'flipx_theme_setup');

function flipx_theme_assets(): void
{
    wp_enqueue_style('flipx-main', get_template_directory_uri() . '/assets/css/main.css', [], '1.0.0');
    wp_enqueue_script('flipx-main', get_template_directory_uri() . '/assets/js/main.js', ['jquery'], '1.0.0', true);

    $cards = function_exists('flipx_engine_get_cards') ? flipx_engine_get_cards() : [];
    $wallet = is_user_logged_in() && function_exists('flipx_wallet_get_balance') ? flipx_wallet_get_balance(get_current_user_id()) : 0;

    wp_localize_script('flipx-main', 'flipxTheme', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('flipx_game_nonce'),
        'cards' => array_values($cards),
        'wallet' => number_format((float) $wallet, 2, '.', ''),
        'isLoggedIn' => is_user_logged_in(),
        'loginUrl' => wp_login_url(get_permalink()),
    ]);
}
add_action('wp_enqueue_scripts', 'flipx_theme_assets');
