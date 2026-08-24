# Local XAMPP setup

1. Put this folder in `C:\xampp\htdocs\masha-allah-mobile-shop`.
2. Start Apache and MySQL.
3. Open `http://localhost/masha-allah-mobile-shop/`. On its first localhost request, the application automatically creates the `masha_allah_shop` database when needed, applies `schema.sql`, and loads the supplied **48-product catalogue**. Do **not** import `schema.sql` or run `seed.php` for a normal first-time local installation.
4. If your MySQL root account is not the normal XAMPP `root` account with an empty password, set the matching `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `DB_PORT` values in your Apache/XAMPP environment before opening the site.
5. To create the initial private administrator, temporarily set `ALLOW_INITIAL_SETUP=1` in the Apache/XAMPP environment, then open `http://localhost/masha-allah-mobile-shop/setup.php`. Create an administrator account of your choice, set `ALLOW_INITIAL_SETUP=0` again, and delete `setup.php` after successful setup. Alternatively, for the requested account, import `admin_credentials.sql` into the `masha_allah_shop` database through phpMyAdmin once, then delete that SQL file from the web root.
6. Copy `config/email.local.example.php` to `config/email.local.php` and enter a **new** Brevo SMTP credential if you want customers to receive OTP messages. Without SMTP configuration, signup and resend deliberately show a clear delivery error rather than claiming that an email was sent.
7. Copy `config/xai.local.example.php` to `config/xai.local.php` and enter your xAI key if you want MobiSaathi live AI. Without a key, the built-in support and operations FAQ fallback remains available.
8. `seed.php` is now a localhost-only **manual recovery tool** for an existing database whose catalogue was intentionally cleared. It is not part of the usual first-run steps.

Do not upload `config/*.local.php` to GitHub. Never paste API, SMTP, or administrator passwords into public files.
