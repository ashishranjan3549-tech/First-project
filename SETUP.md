# FlipX Deployment & Setup Instructions

## 1) Install Theme
1. Zip `flipx-theme` folder.
2. WordPress Admin → Appearance → Themes → Add New → Upload Theme.
3. Activate **FlipX Gaming Theme**.
4. Create a page named `Game`, assign template **FlipX Game**.

## 2) Install Plugin
1. Zip `flipx-engine` folder.
2. WordPress Admin → Plugins → Add New → Upload Plugin.
3. Activate **FlipX Engine**.
4. Activation creates all required DB tables and defaults.

## 3) Install WooCommerce
1. Install and activate WooCommerce.
2. Complete basic store setup (currency INR, payment gateway, emails).

## 4) Create Recharge Product
1. Products → Add New.
2. Name: **Wallet Recharge**.
3. Product type: **Simple product** + **Virtual**.
4. Set any regular price (runtime value is replaced by user selected recharge amount).
5. Publish and note Product ID.
6. FlipX Engine admin → Game Settings → set **Recharge Product ID**.

## 5) Configure Game
WordPress Admin → FlipX Engine:
- Set round duration, pause duration, card price, win multiplier.
- Set or edit cards JSON.
- Save settings.

## 6) Test End-to-End Flow
1. Register new player account from game page.
2. Login.
3. Click **Add to Wallet** (redirects to WooCommerce checkout).
4. Complete order and mark order Completed.
5. Verify wallet credit and transaction row.
6. Place bet with quantity.
7. Wait round end and confirm lowest booked card wins.
8. Verify winner credit (4x multiplier default).
9. Submit withdrawal request.
10. Admin approves/rejects in FlipX Engine panel.

## 7) Hostinger Production Deployment Notes
- PHP 8+ required.
- MySQL/MariaDB supported by WordPress.
- No Node.js services needed.
- WP-Cron must remain enabled (round tick uses WP scheduled events and init fallback).
- Configure SMTP plugin for reliable transactional emails.
- Enable HTTPS and force SSL login/admin.

## 8) Security Checklist
- Keep WordPress, theme, plugin, WooCommerce updated.
- Use strong admin credentials and 2FA.
- Install WAF/security plugin.
- Restrict admin access by IP if possible.
- Backup DB daily.
