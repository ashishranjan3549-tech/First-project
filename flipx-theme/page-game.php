<?php
/**
 * Template Name: FlipX Game
 */
get_header();

$cards = function_exists('flipx_engine_get_cards') ? flipx_engine_get_cards() : [];
?>
<section class="flipx-panel">
    <div class="flipx-top-actions">
        <button class="flipx-btn" id="flipxAddWalletBtn">Add to Wallet</button>
    </div>
    <div class="flipx-timer" id="flipxTimer">00:00:00</div>
    <div class="flipx-status" id="flipxRoundStatus">Fetching current round...</div>

    <?php if (!is_user_logged_in()) : ?>
        <div class="flipx-auth-grid">
            <form id="flipxRegisterForm" class="flipx-auth-card">
                <h3>Create Account</h3>
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="flipx-btn">Register</button>
            </form>
            <form id="flipxLoginForm" class="flipx-auth-card">
                <h3>Login</h3>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="flipx-btn">Login</button>
                <button type="button" class="flipx-link-btn" id="flipxForgotPasswordBtn">Forgot Password</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="flipx-card-grid" id="flipxCardGrid">
        <?php foreach ($cards as $card) : ?>
            <article class="card flipx-card" data-card-id="<?php echo esc_attr((int) $card['id']); ?>">
                <div class="card-inner flipx-card-inner">
                    <div class="card-front flipx-card-front">
                        <img src="<?php echo esc_url($card['image']); ?>" alt="<?php echo esc_attr($card['name']); ?>">
                        <h4><?php echo esc_html($card['name']); ?></h4>
                        <button class="flipx-btn place-bet-btn" data-card-id="<?php echo esc_attr((int) $card['id']); ?>">Place Bet</button>
                    </div>
                    <div class="card-back flipx-card-back">
                        <span class="flipx-result-label"></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="flipx-modal" id="flipxBetModal" aria-hidden="true">
    <div class="flipx-modal-box">
        <button class="flipx-close" id="flipxCloseModal">×</button>
        <h3 id="flipxModalCardName"></h3>
        <img id="flipxModalCardImage" src="" alt="Card image">
        <label>Quantity</label>
        <input type="number" id="flipxBetQty" min="1" value="1">
        <button class="flipx-btn" id="flipxConfirmBet">Confirm Bet</button>
    </div>
</div>
<?php
get_footer();
