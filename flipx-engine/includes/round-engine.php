<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_round_get_active()
{
    global $wpdb;
    $table = $wpdb->prefix . 'flipx_rounds';
    return $wpdb->get_row("SELECT * FROM {$table} WHERE status = 'active' ORDER BY id DESC LIMIT 1");
}

function flipx_round_ensure_active(): void
{
    if (flipx_round_get_active()) {
        return;
    }

    global $wpdb;
    $duration = max(15, (int) get_option('flipx_round_duration', 60));
    $table = $wpdb->prefix . 'flipx_rounds';
    $now = current_time('mysql');
    $current_time = current_time('timestamp');
    $end = gmdate('Y-m-d H:i:s', $current_time + $duration);

    $wpdb->insert($table, [
        'start_time' => $now,
        'end_time' => $end,
        'status' => 'active',
        'winning_card' => null,
    ], ['%s', '%s', '%s', '%d']);
}

function flipx_round_tick_handler(): void
{
    global $wpdb;
    $rounds = $wpdb->prefix . 'flipx_rounds';
    $current_time = current_time('timestamp');

    $active = flipx_round_get_active();
    if (!$active) {
        $last = $wpdb->get_row("SELECT * FROM {$rounds} ORDER BY id DESC LIMIT 1");
        if ($last && $last->status === 'paused' && strtotime($last->end_time . ' UTC') > $current_time) {
            return;
        }
        flipx_round_ensure_active();
        return;
    }

    $end = strtotime($active->end_time . ' UTC');
    if ($end > $current_time) {
        return;
    }

    flipx_round_finalize((int) $active->id);
}

function flipx_round_finalize(int $round_id): void
{
    global $wpdb;
    $bets_table = $wpdb->prefix . 'flipx_bets';
    $rounds_table = $wpdb->prefix . 'flipx_rounds';

    $booked = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT card_id FROM {$bets_table} WHERE round_id = %d ORDER BY card_id ASC",
        $round_id
    ));
    $winning_card = !empty($booked) ? (int) $booked[0] : null;

    if ($winning_card !== null) {
        $win_multiplier = (float) get_option('flipx_win_multiplier', 4);
        $winners = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, SUM(total_amount) as total FROM {$bets_table} WHERE round_id = %d AND card_id = %d GROUP BY user_id",
            $round_id,
            $winning_card
        ));

        foreach ($winners as $winner) {
            $win_amount = (float) $winner->total * $win_multiplier;
            flipx_wallet_credit((int) $winner->user_id, $win_amount, 'round_win_' . $round_id, 'win');
            $user = get_userdata((int) $winner->user_id);
            if ($user) {
                wp_mail($user->user_email, 'FlipX Round Win', 'Congratulations! You won ₹' . number_format($win_amount, 2) . ' in round #' . $round_id);
            }
        }
    }

    $pause = max(10, (int) get_option('flipx_pause_duration', 30));
    $current_time = current_time('timestamp');
    $pause_end = gmdate('Y-m-d H:i:s', $current_time + $pause);

    $wpdb->update($rounds_table, [
        'status' => 'paused',
        'winning_card' => $winning_card,
        'end_time' => $pause_end,
    ], ['id' => $round_id], ['%s', '%d', '%s'], ['%d']);
}

function flipx_get_round_state(): array
{
    global $wpdb;
    $rounds = $wpdb->prefix . 'flipx_rounds';

    flipx_round_tick_handler();
    $round = flipx_round_get_active();
    $current_time = current_time('timestamp');

    if (!$round) {
        $last = $wpdb->get_row("SELECT * FROM {$rounds} ORDER BY id DESC LIMIT 1");
        if ($last && $last->status === 'paused') {
            $end_time = strtotime($last->end_time . ' UTC');
            $pause_remaining_seconds = max(0, (int) ($end_time - $current_time));
            return [
                'round_id' => (int) $last->id,
                'status' => 'finished',
                'status_text' => 'Result declared. Next round starts soon.',
                'remaining_seconds' => 0,
                'pause_remaining_seconds' => (int) $pause_remaining_seconds,
                'winning_card' => $last->winning_card ? (int) $last->winning_card : null,
            ];
        }

        flipx_round_ensure_active();
        $round = flipx_round_get_active();

        if (!$round) {
            return [
                'round_id' => 0,
                'status' => 'active',
                'status_text' => 'Starting round...',
                'remaining_seconds' => 0,
                'pause_remaining_seconds' => 0,
                'winning_card' => null,
            ];
        }
    }

    $end_time = strtotime($round->end_time . ' UTC');
    $remaining_seconds = max(0, (int) ($end_time - $current_time));

    return [
        'round_id' => (int) $round->id,
        'status' => 'active',
        'status_text' => 'Round #' . (int) $round->id . ' is live.',
        'remaining_seconds' => (int) $remaining_seconds,
        'pause_remaining_seconds' => 0,
        'winning_card' => null,
    ];
}
