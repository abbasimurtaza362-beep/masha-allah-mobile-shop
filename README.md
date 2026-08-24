# Masha Allah Mobile & EasyPaisa Shop — PHP/XAMPP Submission

This standalone **PHP 8+ and MySQL/MariaDB** project is structured for the Assessment Plan A requirements. It provides responsive public pages, secure PDO-backed administration, image upload validation, a contact form, product/service/category CRUD, private administration, customer authentication with email OTP verification, five related database tables, and an inventory seeder containing the 48 supplied products.

## XAMPP installation

1. Extract the submission ZIP. Rename the extracted project folder to `masha-allah-mobile-shop`, then copy that folder into `C:\xampp\htdocs\`. The resulting path must be `C:\xampp\htdocs\masha-allah-mobile-shop\index.php`.
2. Confirm the local database settings in `config/database.php`; normal XAMPP defaults are `root` with an empty password. If your installation differs, provide `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `DB_PORT` through the Apache/XAMPP environment.
3. Visit `http://localhost/masha-allah-mobile-shop/`. On the first localhost request, the application automatically creates the configured database when needed, applies the schema, and seeds the supplied **48-product catalogue**. A manual `schema.sql` import and `seed.php` run are no longer required for a normal fresh installation.
4. Before initial administrator setup, temporarily set the Apache/XAMPP environment value `ALLOW_INITIAL_SETUP=1`. Open `http://localhost/masha-allah-mobile-shop/setup.php` once and create an owner account. Then set the value back to `0` and delete `setup.php`.
5. Customer login is `login.php`; customer signup is `signup.php`. The private owner portal is `admin/login.php` and is intentionally not linked from the public website.
6. For email OTP, create a NEW Brevo SMTP key, verify your sender email in Brevo, and copy `config/email.local.example.php` to `config/email.local.php` and enter the credentials there. If SMTP is not configured or email delivery fails, signup and resend show an error and do not create a pending customer account or claim success.
7. `seed.php` remains available only as a localhost manual recovery option when the catalogue has been deliberately removed. It is not a mandatory setup step. If the database already existed before this upgrade, import `update.sql` once instead of importing the full schema again. Then import `security_hardening.sql` **once before deploying this hardened code**. If product cards show broken images, import `fix_image_paths.sql` once to update legacy PNG image references to the supplied WebP assets.

## Security configuration

The project no longer loads real SMTP or xAI credentials from PHP files. Copy the variable names from `config/.env.example` into Apache/XAMPP environment configuration and keep all real values outside the project folder and ZIP files. The original SMTP secret must be revoked/rotated at the provider before using a new environment value.

For a public site, configure HTTPS first, then set `APP_FORCE_HTTPS=1` and `APP_PUBLIC_HOST` to the exact public host name. The included `.htaccess` files deny direct access to configuration, SQL, documentation, and executable upload extensions where Apache `mod_rewrite`/`mod_authz_core` are enabled.

## Security implemented

The project uses PDO prepared statements, `password_hash` and `password_verify`, session regeneration on login, CSRF tokens for form submissions, output escaping, admin authorization, whitelisted image MIME types, maximum file size validation, randomized filenames, and no direct use of client-provided filenames.

> For a classroom demonstration, keep the setup and seed scripts only long enough to initialize localhost. Do not leave them accessible on a public server.


## Navigation and MobiSaathi
Public navigation order: Home, Products, Services, Contact, About. MobiSaathi is loaded globally on public pages and the private owner console.
