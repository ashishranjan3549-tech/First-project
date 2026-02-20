<?php
if (!defined('ABSPATH')) {
    exit;
}
$logo_url = (string) get_option('flipx_logo_url', '');
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('flipx-body'); ?>>
<?php wp_body_open(); ?>
<!-- FLIPX HEADER VERSION 2 -->
<header class="flipx-header">
    <button class="flipx-icon-btn" aria-label="Menu" id="flipxMenuToggle">☰</button>
    <div class="flipx-logo-wrap">
        <?php if ($logo_url) : ?>
            <img class="flipx-logo-image" src="<?php echo esc_url($logo_url); ?>" alt="FlipX Logo">
        <?php else : ?>
            <div class="flipx-logo">FLIPX</div>
        <?php endif; ?>
    </div>
    <div class="flipx-wallet-wrap">
        <span>Wallet</span>
        <strong id="flipxWalletBalance">₹<?php echo esc_html(is_user_logged_in() && function_exists('flipx_wallet_get_balance') ? number_format((float) flipx_wallet_get_balance(get_current_user_id()), 2) : '0.00'); ?></strong>
    </div>
</header>
<main class="flipx-main-container">
