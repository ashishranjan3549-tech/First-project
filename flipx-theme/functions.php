<?php
if (!defined('ABSPATH')) {
    exit;
}

function flipx_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    register_nav_menus([
        'primary' => __('Primary Menu', 'flipx-theme'),
    ]);
}
add_action('after_setup_theme', 'flipx_theme_setup');

function flipx_theme_assets(): void
{
    wp_enqueue_style('flipx-main', get_template_directory_uri() . '/assets/css/main.css', [], '1.0.1');
    wp_enqueue_script('flipx-main', get_template_directory_uri() . '/assets/js/main.js', ['jquery'], '1.0.1', true);

    $cards = function_exists('flipx_engine_get_cards') ? flipx_engine_get_cards() : [];
    $wallet = is_user_logged_in() && function_exists('flipx_wallet_get_balance') ? flipx_wallet_get_balance(get_current_user_id()) : 0;

    wp_localize_script('flipx-main', 'flipxTheme', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('flipx_game_nonce'),
        'cards' => array_values($cards),
        'wallet' => number_format((float) $wallet, 2, '.', ''),
        'isLoggedIn' => is_user_logged_in(),
        'loginUrl' => wp_login_url(get_permalink()),
    ]);
}
add_action('wp_enqueue_scripts', 'flipx_theme_assets');

function flipx_theme_admin_menu(): void
{
    add_theme_page('FlipX Theme Settings', 'Theme Settings', 'manage_options', 'flipx-theme-settings', 'flipx_theme_settings_page');
}
add_action('admin_menu', 'flipx_theme_admin_menu');

function flipx_theme_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['flipx_theme_save'])) {
        check_admin_referer('flipx_theme_settings');
        $logo_url = esc_url_raw(wp_unslash($_POST['flipx_logo_url'] ?? ''));
        update_option('flipx_logo_url', $logo_url);
        echo '<div class="updated"><p>Theme settings saved.</p></div>';
    }

    $logo_url = (string) get_option('flipx_logo_url', '');
    ?>
    <div class="wrap">
        <h1>FlipX Theme Settings</h1>
        <form method="post">
            <?php wp_nonce_field('flipx_theme_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="flipx_logo_url">Header Logo URL (192x192 recommended)</label></th>
                    <td>
                        <input type="url" class="regular-text" id="flipx_logo_url" name="flipx_logo_url" value="<?php echo esc_attr($logo_url); ?>">
                        <p class="description">Upload an image in Media Library and paste URL.</p>
                    </td>
                </tr>
            </table>
            <p><button class="button button-primary" name="flipx_theme_save" value="1">Save Settings</button></p>
        </form>
    </div>
    <?php
}
