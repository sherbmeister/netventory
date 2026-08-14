# Netventory

Netventory is a mobile-friendly PHP network inventory app for hosts, ports, MAC addresses, device types, operating systems, tags, notes, and live reachability checks.

## Features

- Per-user accounts, with each user managing a private inventory.
- Email confirmation for new registrations.
- TOTP two-factor authentication with local QR code generation.
- Trusted devices so 2FA can be skipped for a configurable window.
- Installable PWA shell for phones and tablets.
- CSV import/export and JSON export.
- Per-host TCP port checks, optional ICMP ping checks, and draggable tile ordering.
- Flat-file JSON storage, suitable for simple aaPanel PHP hosting.

## Configuration

Copy `.env.example` into your web server environment or configure these values in aaPanel/PHP-FPM:

```bash
NETVENTORY_BASE_URL=https://netventory.quantumnet.space
NETVENTORY_MAIL_FROM="Netventory <no-reply@netventory.quantumnet.space>"
NETVENTORY_REGISTRATION_OPEN=true
NETVENTORY_REQUIRE_EMAIL_CONFIRMATION=true
NETVENTORY_TRUST_DEVICE_DAYS=21
NETVENTORY_ALLOW_PING=true
```

The app stores runtime data under `data/` by default. Do not commit `data/users.json`, `data/inventories/*.json`, sessions, logs, or backups.

## Deployment

1. Upload the app to the web root.
2. Ensure PHP can write to `data/` and `_sessions/`.
3. Configure the vhost for HTTPS through your reverse proxy or Cloudflare origin setup.
4. Confirm PHP `mail()` works, or configure the server mail transport before opening registration.

If `data/iplist.json` exists from the old LAN app, the first confirmed account inherits it once. New users start with an empty inventory.

## Security Notes

- Passwords are hashed with PHP `password_hash`.
- Email confirmation tokens, trusted-device tokens, and 2FA checks use hashed or time-limited values.
- `.htaccess` blocks direct web access to `data/`, `_sessions/`, and `vendor/`.
- The QR code is generated locally by the bundled PHP QR library, so TOTP secrets are not sent to a third-party QR service.
