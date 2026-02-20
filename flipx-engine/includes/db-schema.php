<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_engine_create_tables(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();
    $wallets = $wpdb->prefix . 'flipx_wallets';
    $transactions = $wpdb->prefix . 'flipx_transactions';
    $rounds = $wpdb->prefix . 'flipx_rounds';
    $bets = $wpdb->prefix . 'flipx_bets';
    $withdrawals = $wpdb->prefix . 'flipx_withdrawals';

    dbDelta("CREATE TABLE {$wallets} (
        user_id BIGINT(20) UNSIGNED NOT NULL,
        balance DECIMAL(12,2) NOT NULL DEFAULT 0,
        PRIMARY KEY (user_id)
    ) {$charset};");

    dbDelta("CREATE TABLE {$transactions} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        type VARCHAR(30) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        reference VARCHAR(190) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_created (user_id, created_at)
    ) {$charset};");

    dbDelta("CREATE TABLE {$rounds} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL,
        winning_card BIGINT(20) UNSIGNED NULL,
        PRIMARY KEY (id),
        KEY status_end (status, end_time)
    ) {$charset};");

    dbDelta("CREATE TABLE {$bets} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        round_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        card_id BIGINT(20) UNSIGNED NOT NULL,
        quantity INT(11) NOT NULL,
        total_amount DECIMAL(12,2) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY round_card (round_id, card_id),
        KEY user_round (user_id, round_id)
    ) {$charset};");

    dbDelta("CREATE TABLE {$withdrawals} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        upi_id VARCHAR(120) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        admin_note TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_status (user_id, status)
    ) {$charset};");
}

function flipx_engine_seed_defaults(): void
{
    $defaults = [
        'round_duration' => 60,
        'pause_duration' => 30,
        'win_multiplier' => 4,
        'card_price' => 10,
        'recharge_product_id' => 0,
        'max_bet_amount' => 2000,
    ];

    foreach ($defaults as $key => $value) {
        if (get_option('flipx_' . $key, null) === null) {
            add_option('flipx_' . $key, $value);
        }
    }

    if (!get_option('flipx_cards')) {
        add_option('flipx_cards', [
            ['id' => 1, 'name' => 'Ace Shadow', 'image' => 'https://picsum.photos/seed/ace/400/300'],
            ['id' => 2, 'name' => 'King Ember', 'image' => 'https://picsum.photos/seed/king/400/300'],
            ['id' => 3, 'name' => 'Queen Nova', 'image' => 'https://picsum.photos/seed/queen/400/300'],
            ['id' => 4, 'name' => 'Joker Blitz', 'image' => 'https://picsum.photos/seed/joker/400/300'],
        ]);
    }
}
