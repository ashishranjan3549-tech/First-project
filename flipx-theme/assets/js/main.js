// WINNER UI FIX VERSION 4
(function ($) {
    let selectedCardId = null;
    let loadingBet = false;
    let lastProcessedRoundId = null;

    const modal = $('#flipxBetModal');

    function call(action, data) {
        return $.post(flipxTheme.ajaxUrl, Object.assign({ action, nonce: flipxTheme.nonce }, data || {}));
    }

    function ensureCardFlipStructure() {
        $('.flipx-card').each(function () {
            const $card = $(this);
            $card.addClass('card');
            if ($card.children('.flipx-card-inner').length) return;

            const $front = $('<div class="flipx-card-front"></div>');
            $front.append($card.children().detach());

            const $back = $('<div class="flipx-card-back"><span class="flipx-result-label">ROUND RESULT</span></div>');
            const $inner = $('<div class="flipx-card-inner"></div>');

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
        if (!data || !data.winning_card) return;

        console.log('Winning Card:', data.winning_card);

        if (data.round_id !== lastProcessedRoundId) {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card) => {
                const id = card.getAttribute('data-card-id');
                if (parseInt(id, 10) === parseInt(data.winning_card, 10)) {
                    card.classList.add('winner');
                    card.classList.add('flipped');
                    const label = card.querySelector('.flipx-result-label');
                    if (label) label.textContent = 'WINNER';
                } else {
                    card.classList.add('loser');
                    const label = card.querySelector('.flipx-result-label');
                    if (label) label.textContent = 'TRY AGAIN';
                }
            });
            lastProcessedRoundId = data.round_id;
        }
    }

    function refreshRound() {
        call('flipx_get_round_state').done(function (res) {
            if (!res.success) return;
            const data = res.data || {};
            $('#flipxRoundStatus').text(data.status_text || '');

            if (data.status === 'active' && lastProcessedRoundId !== null) {
                clearRoundClasses();
                lastProcessedRoundId = null;
            }

            if ((data.status === 'finished' || data.status === 'paused') && data.winning_card) {
                applyRoundResult(data);
            }

            const end = parseInt(data.end_unix, 10) * 1000;
            const now = Date.now();
            const diff = Math.max(0, end - now);
            const h = String(Math.floor(diff / 3600000)).padStart(2, '0');
            const m = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
            const s = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
            $('#flipxTimer').text([h, m, s].join(':'));
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
    setInterval(refreshRound, 1000);
    refreshRound();
})(jQuery);
