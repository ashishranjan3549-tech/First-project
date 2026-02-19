<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_get_recharge_checkout_url(int $user_id): string
{
    if (!class_exists('WooCommerce')) {
        return '';
    }

    $product_id = (int) get_option('flipx_recharge_product_id', 0);
    if (!$product_id) {
        return '';
    }

    $amount = isset($_GET['amount']) ? max(1, (float) $_GET['amount']) : 100;
    WC()->cart->empty_cart();
    WC()->cart->add_to_cart($product_id, 1, 0, [], ['flipx_recharge_amount' => $amount, 'flipx_user_id' => $user_id]);
    return wc_get_checkout_url();
}

add_filter('woocommerce_get_item_data', function (array $item_data, array $cart_item): array {
    if (isset($cart_item['flipx_recharge_amount'])) {
        $item_data[] = ['name' => 'Recharge Amount', 'value' => '₹' . number_format((float) $cart_item['flipx_recharge_amount'], 2)];
    }
    return $item_data;
}, 10, 2);

add_action('woocommerce_before_calculate_totals', function ($cart): void {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    foreach ($cart->get_cart() as $item) {
        if (isset($item['flipx_recharge_amount'])) {
            $item['data']->set_price((float) $item['flipx_recharge_amount']);
        }
    }
});

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values): void {
    if (isset($values['flipx_recharge_amount'])) {
        $item->add_meta_data('_flipx_recharge_amount', (float) $values['flipx_recharge_amount'], true);
    }
    if (isset($values['flipx_user_id'])) {
        $item->add_meta_data('_flipx_user_id', (int) $values['flipx_user_id'], true);
    }
}, 10, 3);

add_action('woocommerce_order_status_completed', function (int $order_id): void {
    $processed_key = '_flipx_wallet_credited';
    if (get_post_meta($order_id, $processed_key, true)) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $product_id = (int) get_option('flipx_recharge_product_id', 0);
    $credited = false;

    foreach ($order->get_items() as $item) {
        if ((int) $item->get_product_id() !== $product_id) {
            continue;
        }

        $amount = (float) $item->get_meta('_flipx_recharge_amount');
        $user_id = (int) $item->get_meta('_flipx_user_id');
        if (!$user_id) {
            $user_id = (int) $order->get_user_id();
        }

        if ($user_id > 0 && $amount > 0) {
            $ok = flipx_wallet_credit($user_id, $amount, 'wc_order_' . $order_id, 'credit');
            if ($ok) {
                $credited = true;
                $user = get_userdata($user_id);
                if ($user) {
                    wp_mail($user->user_email, 'FlipX Wallet Recharge Successful', 'Your wallet has been credited with ₹' . number_format($amount, 2) . '.');
                }
            }
        }
    }

    if ($credited) {
        update_post_meta($order_id, $processed_key, 1);
    }
});
