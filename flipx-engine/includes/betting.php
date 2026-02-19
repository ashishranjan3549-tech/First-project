<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_engine_get_cards(): array
{
    $cards = get_option('flipx_cards', []);
    return is_array($cards) ? $cards : [];
}

function flipx_engine_get_card(int $card_id): ?array
{
    foreach (flipx_engine_get_cards() as $card) {
        if ((int) $card['id'] === $card_id) {
            return $card;
        }
    }
    return null;
}

function flipx_place_bet_for_user(int $user_id, int $card_id, int $quantity)
{
    global $wpdb;

    if ($quantity < 1) {
        return new WP_Error('invalid_qty', 'Minimum quantity is 1.');
    }

    $card = flipx_engine_get_card($card_id);
    if (!$card) {
        return new WP_Error('invalid_card', 'Invalid card selected.');
    }

    $round = flipx_round_get_active();
    if (!$round) {
        return new WP_Error('round_not_active', 'No active round found.');
    }

    $card_price = (float) get_option('flipx_card_price', 10);
    $max_bet = (float) get_option('flipx_max_bet_amount', 2000);
    $total = $quantity * $card_price;

    if ($total > $max_bet) {
        return new WP_Error('max_bet', 'Bet exceeds max allowed amount.');
    }

    $bets = $wpdb->prefix . 'flipx_bets';
    $current_round_user = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(total_amount), 0) FROM {$bets} WHERE round_id = %d AND user_id = %d",
        (int) $round->id,
        $user_id
    ));

    if (($current_round_user + $total) > $max_bet) {
        return new WP_Error('max_bet', 'Total round bet exceeds max allowed amount.');
    }

    $lock_key = 'flipx_bet_lock_' . $user_id;
    if (get_transient($lock_key)) {
        return new WP_Error('duplicate_submission', 'Please wait, processing previous bet.');
    }
    set_transient($lock_key, 1, 5);

    $reference = 'bet_round_' . (int) $round->id . '_card_' . $card_id;
    $debited = flipx_wallet_debit($user_id, $total, $reference, 'bet');
    if (!$debited) {
        delete_transient($lock_key);
        return new WP_Error('insufficient_balance', 'Insufficient wallet balance.');
    }

    $ok = $wpdb->insert($bets, [
        'round_id' => (int) $round->id,
        'user_id' => $user_id,
        'card_id' => $card_id,
        'quantity' => $quantity,
        'total_amount' => $total,
    ], ['%d', '%d', '%d', '%d', '%f']);

    delete_transient($lock_key);

    if (!$ok) {
        flipx_wallet_credit($user_id, $total, 'bet_reversal_' . $reference, 'credit');
        return new WP_Error('bet_failed', 'Could not place bet. Amount refunded.');
    }

    return [
        'message' => 'Bet placed successfully.',
        'balance' => flipx_wallet_get_balance($user_id),
    ];
}
