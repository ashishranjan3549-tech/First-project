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

    // TIMER DESKTOP FIX
    function normalizeRoundData(response) {
        const data = (response && response.data) ? response.data : {};
        return {
            round_id: parseInt(data.round_id || 0, 10),
            status: String(data.status || ''),
            status_text: String(data.status_text || ''),
            remaining_seconds: parseInt(data.remaining_seconds || 0, 10) || 0,
            pause_remaining_seconds: parseInt(data.pause_remaining_seconds || 0, 10) || 0,
            winning_card: data.winning_card !== null && data.winning_card !== undefined ? parseInt(data.winning_card, 10) : null,
        };
    }

    function formatTime(seconds) {
        const safe = Math.max(0, parseInt(seconds, 10) || 0);
        const hrs = Math.floor(safe / 3600);
        const mins = Math.floor((safe % 3600) / 60);
        const secs = safe % 60;
        return String(hrs).padStart(2, '0') + ':' + String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    function updateTimerUI(seconds) {
        $('#flipxTimer').text(formatTime(seconds));
    }

    function startCountdown(seconds) {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        let remaining = parseInt(seconds || 0, 10);
        if (isNaN(remaining) || remaining <= 0) {
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

    // FLIP STRUCTURE FIX
    function ensureCardFlipStructure() {
        $('.card').each(function () {
            const $card = $(this);
            const $inner = $card.children('.card-inner');
            if ($inner.length) {
                return;
            }

            const existingChildren = $card.children().detach();
            const $front = $('<div class="card-front"></div>');
            const $back = $('<div class="card-back"><span class="flipx-result-label"></span></div>');
            const $newInner = $('<div class="card-inner"></div>');

            $front.append(existingChildren);
            $newInner.append($front, $back);
            $card.append($newInner);
        });
    }

    function clearRoundClasses() {
        document.querySelectorAll('.card').forEach(function (card) {
            card.classList.remove('flipped', 'winner', 'loser');
            const resultLabel = card.querySelector('.flipx-result-label');
            if (resultLabel) {
                resultLabel.textContent = '';
            }
        });
    }

    // WINNER MATCH FIX
    function applyRoundResult(data) {
        if (!(data.status === 'finished' && data.winning_card && data.round_id !== lastProcessedRoundId)) {
            return;
        }

        document.querySelectorAll('.card').forEach(function (card) {
            const cardId = card.getAttribute('data-card-id');
            console.log('Winner ID:', data.winning_card);
            console.log('Card ID:', cardId);

            card.classList.add('flipped');
            if (parseInt(cardId, 10) === parseInt(data.winning_card, 10)) {
                card.classList.add('winner');
                const resultLabel = card.querySelector('.flipx-result-label');
                if (resultLabel) {
                    resultLabel.textContent = 'WINNER';
                }
            } else {
                card.classList.add('loser');
                const resultLabel = card.querySelector('.flipx-result-label');
                if (resultLabel) {
                    resultLabel.textContent = 'TRY AGAIN';
                }
            }
        });

        lastProcessedRoundId = data.round_id;
    }

    function refreshRound() {
        call('flipx_get_round_state').done(function (response) {
            if (!response || !response.success) return;

            const data = normalizeRoundData(response);
            console.log('Round State:', data);
            $('#flipxRoundStatus').text(data.status_text);

            if (data.status === 'active') {
                if (lastProcessedRoundId !== null && data.round_id !== lastProcessedRoundId) {
                    clearRoundClasses();
                    lastProcessedRoundId = null;
                }
                startCountdown(data.remaining_seconds);
            } else if (data.status === 'finished') {
                applyRoundResult(data);
                startCountdown(data.pause_remaining_seconds);
            } else {
                startCountdown(0);
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
