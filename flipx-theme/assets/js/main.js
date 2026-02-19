// FLIP ANIMATION FINAL FIX
(function ($) {
    let selectedCardId = null;
    let loadingBet = false;
    let lastProcessedRoundId = null;
    let countdownInterval = null;

    const modal = $('#flipxBetModal');

    function call(action, data) {
        return $.post(flipxTheme.ajaxUrl, Object.assign({ action, nonce: flipxTheme.nonce }, data || {}));
    }

    function ensureCardFlipStructure() {
        $('.flipx-card').each(function () {
            const $card = $(this);
            $card.addClass('card');
            if ($card.children('.flipx-card-inner').length) return;

            const $front = $('<div class="flipx-card-front card-front"></div>');
            $front.append($card.children().detach());

            const $back = $('<div class="flipx-card-back card-back"><span class="flipx-result-label">ROUND RESULT</span></div>');
            const $inner = $('<div class="flipx-card-inner card-inner"></div>');

            $inner.append($front, $back);
            $card.append($inner);
        });
    }

    function clearRoundClasses() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card) => {
            card.classList.remove('winner', 'loser', 'flipped');
            const label = card.querySelector('.flipx-result-label');
            if (label) label.textContent = 'ROUND RESULT';
        });
    }

    function applyRoundResult(data) {
        if (
            data.status === 'finished' &&
            data.winning_card &&
            data.round_id !== lastProcessedRoundId
        ) {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card) => {
                const id = card.getAttribute('data-card-id');
                if (parseInt(id, 10) === parseInt(data.winning_card, 10)) {
                    card.classList.add('winner');
                    card.classList.add('flipped');
                    const label = card.querySelector('.flipx-result-label');
                    if (label) label.textContent = 'WINNER';
                } else {
                    card.classList.add('flipped');
                    card.classList.add('loser');
                    const label = card.querySelector('.flipx-result-label');
                    if (label) label.textContent = 'TRY AGAIN';
                }
            });
            lastProcessedRoundId = data.round_id;
        }
    }

    function formatTime(seconds) {
        seconds = Math.max(0, parseInt(seconds, 10) || 0);

        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        return (
            String(hrs).padStart(2, '0') + ':' +
            String(mins).padStart(2, '0') + ':' +
            String(secs).padStart(2, '0')
        );
    }

    function updateTimerUI(seconds) {
        $('#flipxTimer').text(formatTime(seconds));
    }

    function startCountdown(seconds) {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        let remaining = parseInt(seconds, 10);
        if (isNaN(remaining)) {
            remaining = 0;
        }
        if (remaining <= 0) {
            remaining = 0;
        }

        updateTimerUI(remaining);

        countdownInterval = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                remaining = 0;
                updateTimerUI(remaining);
                clearInterval(countdownInterval);
                countdownInterval = null;
                return;
            }
            updateTimerUI(remaining);
        }, 1000);
    }

    function refreshRound() {
        call('flipx_get_round_state').done(function (res) {
            if (!res.success) return;
            const data = res.data || {};
            console.log('Round State:', data);
            $('#flipxRoundStatus').text(data.status_text || '');

            if (data.status === 'active') {
                if (lastProcessedRoundId !== null && data.round_id !== lastProcessedRoundId) {
                    clearRoundClasses();
                    lastProcessedRoundId = null;
                }

                let remaining = parseInt(data.remaining_seconds, 10);
                if (isNaN(remaining)) {
                    remaining = 0;
                }
                if (remaining <= 0) remaining = 0;

                startCountdown(remaining);
            } else if (data.status === 'finished') {
                applyRoundResult(data);

                let remaining = parseInt(data.pause_remaining_seconds, 10);
                if (isNaN(remaining)) {
                    remaining = 0;
                }
                if (remaining <= 0) remaining = 0;

                startCountdown(remaining);
            }
        });
    }

    function updateWallet(balance) {
        $('#flipxWalletBalance').text('₹' + Number(balance).toFixed(2));
    }

    $(document).on('click', '.place-bet-btn', function () {
        const cardId = $(this).data('card-id');
        const card = (flipxTheme.cards || []).find(c => parseInt(c.id, 10) === parseInt(cardId, 10));
        if (!flipxTheme.isLoggedIn) {
            window.location.href = flipxTheme.loginUrl;
            return;
        }
        if (!card) return;
        selectedCardId = card.id;
        $('#flipxModalCardName').text(card.name);
        $('#flipxModalCardImage').attr('src', card.image);
        $('#flipxBetQty').val(1);
        modal.addClass('open').attr('aria-hidden', 'false');
    });

    $('#flipxCloseModal').on('click', function () {
        modal.removeClass('open').attr('aria-hidden', 'true');
    });

    $('#flipxConfirmBet').on('click', function () {
        if (loadingBet || !selectedCardId) return;
        loadingBet = true;
        call('flipx_place_bet', { card_id: selectedCardId, quantity: $('#flipxBetQty').val() }).done(function (res) {
            alert(res.data.message || 'Request complete');
            if (res.success) {
                updateWallet(res.data.balance);
                modal.removeClass('open').attr('aria-hidden', 'true');
            }
        }).always(function () { loadingBet = false; });
    });

    $('#flipxAddWalletBtn').on('click', function () {
        call('flipx_get_recharge_url').done(function (res) {
            if (res.success) window.location.href = res.data.url;
        });
    });

    $('#flipxRegisterForm').on('submit', function (e) {
        e.preventDefault();
        call('flipx_register_user', $(this).serialize()).done((res) => alert(res.data.message));
    });

    $('#flipxLoginForm').on('submit', function (e) {
        e.preventDefault();
        call('flipx_login_user', $(this).serialize()).done((res) => {
            alert(res.data.message);
            if (res.success) window.location.reload();
        });
    });

    $('#flipxForgotPasswordBtn').on('click', function () {
        const email = $('#flipxLoginForm [name="email"]').val();
        call('flipx_forgot_password', { email }).done((res) => alert(res.data.message));
    });

    ensureCardFlipStructure();
    refreshRound();
    setInterval(refreshRound, 5000);
})(jQuery);
