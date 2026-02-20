<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_wallet_get_balance(int $user_id): float
{
    global $wpdb;
    $table = $wpdb->prefix . 'flipx_wallets';
    $balance = $wpdb->get_var($wpdb->prepare("SELECT balance FROM {$table} WHERE user_id = %d", $user_id));
    if ($balance === null) {
        $wpdb->insert($table, ['user_id' => $user_id, 'balance' => 0], ['%d', '%f']);
        return 0.0;
    }
    return (float) $balance;
}

function flipx_wallet_add_transaction(int $user_id, string $type, float $amount, string $reference): void
{
    global $wpdb;
    $table = $wpdb->prefix . 'flipx_transactions';
    $wpdb->insert($table, [
        'user_id' => $user_id,
        'type' => sanitize_text_field($type),
        'amount' => $amount,
        'reference' => sanitize_text_field($reference),
    ], ['%d', '%s', '%f', '%s']);
}

function flipx_wallet_credit(int $user_id, float $amount, string $reference, string $txn_type = 'credit'): bool
{
    if ($amount <= 0) {
        return false;
    }

    global $wpdb;
    $wallets = $wpdb->prefix . 'flipx_wallets';
    $wpdb->query('START TRANSACTION');

    $current = flipx_wallet_get_balance($user_id);
    $updated = $wpdb->update($wallets, ['balance' => $current + $amount], ['user_id' => $user_id], ['%f'], ['%d']);

    if ($updated === false) {
        $wpdb->query('ROLLBACK');
        return false;
    }

    flipx_wallet_add_transaction($user_id, $txn_type, $amount, $reference);
    $wpdb->query('COMMIT');
    return true;
}

function flipx_wallet_debit(int $user_id, float $amount, string $reference, string $txn_type = 'debit'): bool
{
    if ($amount <= 0) {
        return false;
    }

    global $wpdb;
    $wallets = $wpdb->prefix . 'flipx_wallets';
    $wpdb->query('START TRANSACTION');

    $affected = $wpdb->query($wpdb->prepare(
        "UPDATE {$wallets} SET balance = balance - %f WHERE user_id = %d AND balance >= %f",
        $amount,
        $user_id,
        $amount
    ));

    if (!$affected) {
        $wpdb->query('ROLLBACK');
        return false;
    }

    flipx_wallet_add_transaction($user_id, $txn_type, $amount, $reference);
    $wpdb->query('COMMIT');
    return true;
}
