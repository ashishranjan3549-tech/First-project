<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .flipx-header { min-height: 58px !important; padding-top: 4px !important; padding-bottom: 4px !important; }
        .flipx-logo, .flipx-logo-image { width: 192px !important; height: 192px !important; }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class('flipx-body'); ?>>
<?php wp_body_open(); ?>
<!-- HEADER UPDATED VERSION 2 -->
<header class="flipx-header">
    <button class="flipx-icon-btn" aria-label="Menu" id="flipxMenuToggle">☰</button>
    <div class="flipx-logo-wrap">
        <div class="flipx-logo">FLIPX</div>
    </div>
    <div class="flipx-wallet-wrap">
        <span>Wallet</span>
        <strong id="flipxWalletBalance">₹<?php echo esc_html(is_user_logged_in() && function_exists('flipx_wallet_get_balance') ? number_format((float) flipx_wallet_get_balance(get_current_user_id()), 2) : '0.00'); ?></strong>
    </div>
</header>
<main class="flipx-main-container">
