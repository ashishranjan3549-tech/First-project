<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function (): void {
    add_menu_page('FlipX Engine', 'FlipX Engine', 'manage_options', 'flipx-engine', 'flipx_admin_render', 'dashicons-games', 56);
});

function flipx_admin_render(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    flipx_admin_handle_post();

    $cards = flipx_engine_get_cards();
    $withdrawals = flipx_admin_get_withdrawals();
    $round_history = flipx_admin_round_history();
    ?>
    <div class="wrap">
        <h1>FlipX Engine</h1>
        <h2>Game Settings</h2>
        <form method="post">
            <?php wp_nonce_field('flipx_admin_settings', 'flipx_admin_nonce'); ?>
            <input type="hidden" name="flipx_action" value="save_settings">
            <table class="form-table">
                <tr><th>Round duration (sec)</th><td><input type="number" name="round_duration" value="<?php echo esc_attr((int) get_option('flipx_round_duration', 60)); ?>"></td></tr>
                <tr><th>Pause duration (sec)</th><td><input type="number" name="pause_duration" value="<?php echo esc_attr((int) get_option('flipx_pause_duration', 30)); ?>"></td></tr>
                <tr><th>Win multiplier</th><td><input type="number" step="0.1" name="win_multiplier" value="<?php echo esc_attr((float) get_option('flipx_win_multiplier', 4)); ?>"></td></tr>
                <tr><th>Card price</th><td><input type="number" step="0.01" name="card_price" value="<?php echo esc_attr((float) get_option('flipx_card_price', 10)); ?>"></td></tr>
                <tr><th>Recharge Product ID</th><td><input type="number" name="recharge_product_id" value="<?php echo esc_attr((int) get_option('flipx_recharge_product_id', 0)); ?>"></td></tr>
            </table>
            <p><button class="button button-primary">Save settings</button></p>
        </form>

        <h3>Cards</h3>
        <form method="post">
            <?php wp_nonce_field('flipx_admin_cards', 'flipx_cards_nonce'); ?>
            <input type="hidden" name="flipx_action" value="save_cards">
            <textarea name="cards_json" rows="10" style="width:100%"><?php echo esc_textarea(wp_json_encode($cards, JSON_PRETTY_PRINT)); ?></textarea>
            <p class="description">JSON format: [{"id":1,"name":"Ace","image":"https://..."}]</p>
            <p><button class="button">Save cards</button></p>
        </form>

        <h2>Wallet Manager</h2>
        <form method="post">
            <?php wp_nonce_field('flipx_admin_wallet', 'flipx_wallet_nonce'); ?>
            <input type="hidden" name="flipx_action" value="wallet_adjust">
            <input type="email" name="wallet_email" placeholder="User email" required>
            <input type="number" step="0.01" name="amount" placeholder="Amount" required>
            <select name="adjust_type"><option value="credit">Credit</option><option value="debit">Debit</option></select>
            <input type="text" name="reason" placeholder="Reason" required>
            <button class="button">Apply</button>
        </form>

        <h2>Withdrawals</h2>
        <table class="widefat striped"><thead><tr><th>ID</th><th>User</th><th>Amount</th><th>UPI</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($withdrawals as $w) : ?>
                <tr>
                    <td><?php echo (int) $w->id; ?></td>
                    <td><?php echo esc_html(get_userdata((int) $w->user_id)->user_email ?? 'Unknown'); ?></td>
                    <td>₹<?php echo esc_html(number_format((float) $w->amount, 2)); ?></td>
                    <td><?php echo esc_html($w->upi_id); ?></td>
                    <td><?php echo esc_html($w->phone); ?></td>
                    <td><?php echo esc_html($w->status); ?></td>
                    <td>
                        <?php if ($w->status === 'pending') : ?>
                            <form method="post" style="display:inline-block;">
                                <?php wp_nonce_field('flipx_admin_withdrawals', 'flipx_withdrawals_nonce'); ?>
                                <input type="hidden" name="flipx_action" value="withdrawal_update">
                                <input type="hidden" name="withdrawal_id" value="<?php echo (int) $w->id; ?>">
                                <input type="hidden" name="status" value="approved">
                                <input type="text" name="admin_note" placeholder="Note">
                                <button class="button button-primary">Approve</button>
                            </form>
                            <form method="post" style="display:inline-block;">
                                <?php wp_nonce_field('flipx_admin_withdrawals', 'flipx_withdrawals_nonce'); ?>
                                <input type="hidden" name="flipx_action" value="withdrawal_update">
                                <input type="hidden" name="withdrawal_id" value="<?php echo (int) $w->id; ?>">
                                <input type="hidden" name="status" value="rejected">
                                <input type="text" name="admin_note" placeholder="Note">
                                <button class="button">Reject</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>

        <h2>Round History (7 days)</h2>
        <table class="widefat striped"><thead><tr><th>Round ID</th><th>Start</th><th>End</th><th>Winning Card</th></tr></thead><tbody>
        <?php foreach ($round_history as $row) : ?>
            <tr><td><?php echo (int) $row->id; ?></td><td><?php echo esc_html($row->start_time); ?></td><td><?php echo esc_html($row->end_time); ?></td><td><?php echo esc_html($row->winning_card ?: '-'); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
    <?php
}

function flipx_admin_handle_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['flipx_action'])) {
        return;
    }

    $action = sanitize_text_field(wp_unslash($_POST['flipx_action']));

    if ($action === 'save_settings' && check_admin_referer('flipx_admin_settings', 'flipx_admin_nonce')) {
        update_option('flipx_round_duration', max(15, (int) $_POST['round_duration']));
        update_option('flipx_pause_duration', max(10, (int) $_POST['pause_duration']));
        update_option('flipx_win_multiplier', max(1, (float) $_POST['win_multiplier']));
        update_option('flipx_card_price', max(1, (float) $_POST['card_price']));
        update_option('flipx_recharge_product_id', max(0, (int) $_POST['recharge_product_id']));
    }

    if ($action === 'save_cards' && check_admin_referer('flipx_admin_cards', 'flipx_cards_nonce')) {
        $json = wp_unslash($_POST['cards_json'] ?? '[]');
        $cards = json_decode($json, true);
        if (is_array($cards)) {
            $sanitized = [];
            foreach ($cards as $card) {
                $sanitized[] = [
                    'id' => (int) ($card['id'] ?? 0),
                    'name' => sanitize_text_field($card['name'] ?? ''),
                    'image' => esc_url_raw($card['image'] ?? ''),
                ];
            }
            update_option('flipx_cards', $sanitized);
        }
    }

    if ($action === 'wallet_adjust' && check_admin_referer('flipx_admin_wallet', 'flipx_wallet_nonce')) {
        $user = get_user_by('email', sanitize_email(wp_unslash($_POST['wallet_email'] ?? '')));
        if ($user) {
            $amount = (float) $_POST['amount'];
            $reason = sanitize_text_field(wp_unslash($_POST['reason'] ?? 'manual_adjust'));
            $type = sanitize_text_field(wp_unslash($_POST['adjust_type'] ?? 'credit'));
            $ok = $type === 'debit'
                ? flipx_wallet_debit((int) $user->ID, $amount, 'admin_debit_' . $reason, 'debit')
                : flipx_wallet_credit((int) $user->ID, $amount, 'admin_credit_' . $reason, 'credit');
            if ($ok) {
                wp_mail($user->user_email, 'FlipX Wallet Updated', 'Your wallet has been ' . $type . 'ed by ₹' . number_format($amount, 2) . '. Reason: ' . $reason);
            }
        }
    }

    if ($action === 'withdrawal_update' && check_admin_referer('flipx_admin_withdrawals', 'flipx_withdrawals_nonce')) {
        flipx_admin_process_withdrawal((int) $_POST['withdrawal_id'], sanitize_text_field($_POST['status']), sanitize_text_field($_POST['admin_note']));
    }
}

function flipx_admin_get_withdrawals(): array
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}flipx_withdrawals ORDER BY id DESC LIMIT 200");
}

function flipx_admin_process_withdrawal(int $id, string $status, string $note): void
{
    global $wpdb;
    $table = $wpdb->prefix . 'flipx_withdrawals';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    if (!$row || $row->status !== 'pending') {
        return;
    }

    $status = in_array($status, ['approved', 'rejected'], true) ? $status : 'rejected';
    $wpdb->update($table, ['status' => $status, 'admin_note' => sanitize_textarea_field($note)], ['id' => $id], ['%s', '%s'], ['%d']);

    if ($status === 'rejected') {
        flipx_wallet_credit((int) $row->user_id, (float) $row->amount, 'withdrawal_reject_refund_' . $id, 'credit');
    }

    $user = get_userdata((int) $row->user_id);
    if ($user) {
        wp_mail($user->user_email, 'FlipX Withdrawal ' . ucfirst($status), 'Your withdrawal request #' . $id . ' was ' . $status . '. Note: ' . $note);
    }
}

function flipx_admin_round_history(): array
{
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare(
        "SELECT id,start_time,end_time,winning_card FROM {$wpdb->prefix}flipx_rounds WHERE start_time >= %s ORDER BY id DESC",
        gmdate('Y-m-d H:i:s', strtotime('-7 days'))
    ));
}
