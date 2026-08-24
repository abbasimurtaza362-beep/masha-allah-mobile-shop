# XAMPP Catalogue and OTP Repair

- [x] Inspect why a fresh XAMPP database can show no product cards.
- [x] Add a safe local setup path that imports schema and provided seed data automatically when appropriate.
- [x] Correct OTP resend timing and prevent success feedback when email delivery fails.
- [x] Verify local catalogue seeding and OTP error reporting.

## Verification Results

On a fresh disposable localhost database, the first PHP request created the schema and seeded exactly 48 products. In the live preview, a deliberately failed email-send attempt displayed the new clear delivery error and left no customer account behind. All modified PHP files passed syntax validation.

## Reported XAMPP Follow-up

- [x] Reproduce the user-reported XAMPP zero-product state using default XAMPP-style database credentials.
- [x] Make the local bootstrap seed categories and products even when a partially initialized database already contains a `products` table.
- [x] Ensure OTP can only report success after the configured SMTP transport accepts the email.
- [ ] Rebuild and retest the no-secret XAMPP download archive.
