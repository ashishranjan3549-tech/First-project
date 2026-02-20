<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_request_withdrawal(int $user_id, float $amount, string $upi_id, string $phone)
{
    if ($amount <= 0) {
        return new WP_Error('invalid_amount', 'Invalid withdrawal amount.');
    }
    $upi_id = sanitize_text_field($upi_id);
    $phone = sanitize_text_field($phone);
    if (empty($upi_id) || empty($phone)) {
        return new WP_Error('invalid_details', 'UPI ID and phone are required.');
    }

    $debited = flipx_wallet_debit($user_id, $amount, 'withdrawal_request', 'withdrawal');
    if (!$debited) {
        return new WP_Error('insufficient_balance', 'Insufficient balance.');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'flipx_withdrawals';
    $ok = $wpdb->insert($table, [
        'user_id' => $user_id,
        'amount' => $amount,
        'upi_id' => $upi_id,
        'phone' => $phone,
        'status' => 'pending',
    ], ['%d', '%f', '%s', '%s', '%s']);

    if (!$ok) {
        flipx_wallet_credit($user_id, $amount, 'withdrawal_reversal', 'credit');
        return new WP_Error('request_failed', 'Could not submit withdrawal request.');
    }

    $user = get_userdata($user_id);
    if ($user) {
        wp_mail($user->user_email, 'FlipX Withdrawal Request', 'Your withdrawal request of ₹' . number_format($amount, 2) . ' has been submitted.');
    }

    return ['message' => 'Withdrawal request submitted successfully.'];
}
