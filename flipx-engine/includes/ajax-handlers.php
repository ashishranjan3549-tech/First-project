<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_ajax_check_nonce(): void
{
    check_ajax_referer('flipx_game_nonce', 'nonce');
}

add_action('wp_ajax_flipx_get_round_state', 'flipx_ajax_get_round_state');
add_action('wp_ajax_nopriv_flipx_get_round_state', 'flipx_ajax_get_round_state');
function flipx_ajax_get_round_state(): void
{
    flipx_ajax_check_nonce();
    wp_send_json_success(flipx_get_round_state());
}

add_action('wp_ajax_flipx_place_bet', 'flipx_ajax_place_bet');
function flipx_ajax_place_bet(): void
{
    flipx_ajax_check_nonce();
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Login required.']);
    }

    $result = flipx_place_bet_for_user(get_current_user_id(), (int) $_POST['card_id'], (int) $_POST['quantity']);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    wp_send_json_success($result);
}

add_action('wp_ajax_flipx_request_withdrawal', 'flipx_ajax_request_withdrawal');
function flipx_ajax_request_withdrawal(): void
{
    flipx_ajax_check_nonce();
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Login required.']);
    }
    $result = flipx_request_withdrawal(get_current_user_id(), (float) $_POST['amount'], (string) $_POST['upi_id'], (string) $_POST['phone']);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    wp_send_json_success($result);
}

add_action('wp_ajax_flipx_get_recharge_url', 'flipx_ajax_get_recharge_url');
function flipx_ajax_get_recharge_url(): void
{
    flipx_ajax_check_nonce();
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Login required.']);
    }
    $url = flipx_get_recharge_checkout_url(get_current_user_id());
    if (!$url) {
        wp_send_json_error(['message' => 'Recharge product not configured.']);
    }
    wp_send_json_success(['url' => $url]);
}

add_action('wp_ajax_nopriv_flipx_register_user', 'flipx_ajax_register_user');
function flipx_ajax_register_user(): void
{
    flipx_ajax_check_nonce();

    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $first_name = sanitize_text_field(wp_unslash($_POST['first_name'] ?? ''));
    $last_name = sanitize_text_field(wp_unslash($_POST['last_name'] ?? ''));

    if (!$email || !$password) {
        wp_send_json_error(['message' => 'Email and password are required.']);
    }
    if (email_exists($email)) {
        wp_send_json_error(['message' => 'Email already exists.']);
    }

    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'user_pass' => $password,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'role' => 'subscriber',
    ]);

    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }

    flipx_wallet_get_balance((int) $user_id);
    wp_send_json_success(['message' => 'Registration successful. Please login.']);
}

add_action('wp_ajax_nopriv_flipx_login_user', 'flipx_ajax_login_user');
function flipx_ajax_login_user(): void
{
    flipx_ajax_check_nonce();
    $creds = [
        'user_login' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        'user_password' => (string) ($_POST['password'] ?? ''),
        'remember' => true,
    ];
    $user = wp_signon($creds, is_ssl());
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Invalid login credentials.']);
    }
    wp_send_json_success(['message' => 'Login successful.']);
}

add_action('wp_ajax_nopriv_flipx_forgot_password', 'flipx_ajax_forgot_password');
function flipx_ajax_forgot_password(): void
{
    flipx_ajax_check_nonce();
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    if (!$email) {
        wp_send_json_error(['message' => 'Email is required.']);
    }

    $user = get_user_by('email', $email);
    if (!$user) {
        wp_send_json_error(['message' => 'No account found for this email.']);
    }

    $result = retrieve_password($user->user_login);
    if ($result) {
        wp_send_json_success(['message' => 'Password reset link sent to your email.']);
    }

    wp_send_json_error(['message' => 'Unable to process request now.']);
}
