# Masha Allah Mobile & EasyPaisa Shop

A standalone **PHP 8+ / MySQL-MariaDB** web application developed for the DTAN's AI Web Development Cohort 2026 Final Project Submission.

The project provides a responsive public business website, database-driven product and service catalogues, customer authentication with email OTP verification, and a protected administrator portal for business management.

## Live Application

**Public Website:**  
https://mashaallahmobile.up.railway.app/

**Admin Login:**  
https://mashaallahmobile.up.railway.app/admin/login.php

**GitHub Repository:**  
https://github.com/abbasimurtaza362-beep/masha-allah-mobile-shop

> Do not store administrator passwords or API keys in this README or in a public GitHub repository. Test credentials should only be provided through a secure evaluation channel when specifically requested.

---

## Project Overview

Masha Allah Mobile & EasyPaisa Shop is a local business in Quetta that needed a professional digital presence for its products, services, customer enquiries, and day-to-day business management.

The application addresses this by combining:

- Public business information pages
- Database-driven product and service catalogues
- Customer registration and email OTP verification
- Customer sign-in
- Protected administrator authentication
- Product, category, service, and inventory management
- Contact and enquiry functionality
- MobiSaathi AI-assisted customer support
- Responsive mobile and desktop UI

---

## Public Website Modules

The public website navigation is organized as:

**Home → Products → Services → Contact → About**

### Home
The landing page introduces the business, highlights important offerings, and provides clear calls to action for customers.

### Products
A database-driven catalogue displaying products with:

- Product images
- Prices
- Categories
- Stock information
- Search
- Category filtering
- Sorting
- Pagination
- Customer enquiry actions

The demonstration database contains **48 products**.

### Services
A dedicated services section for:

- Mobile accessories
- Mobile repairing
- Mobile software services
- Other shop-related services

### Contact
The Contact page provides business location information, map/location details, phone and WhatsApp contact options, and an enquiry path.

### About
The About page provides the business story, context, and information needed to establish trust with visitors.

---

## Customer Authentication

Customers can create an account through `signup.php`.

The registration process includes:

1. Full name
2. Email address
3. Password
4. Password confirmation
5. OTP email verification
6. Account activation
7. Customer login

The application uses password hashing, CSRF protection, session regeneration, output escaping, and OTP verification.

### Customer URLs

**Customer Sign Up:**  
https://mashaallahmobile.up.railway.app/signup.php

**Customer Login:**  
https://mashaallahmobile.up.railway.app/login.php

---

## Admin Portal

The private administrator area is intentionally separated from the public navigation.

### Admin URL

https://mashaallahmobile.up.railway.app/admin/login.php

The admin portal provides management areas for:

- Dashboard
- Products
- Categories
- Services
- Inventory
- Orders
- Sales
- Customer/User management
- Messages / enquiries
- Account settings

The dashboard provides business visibility including product count, stock information, inventory value, sales information, open orders, and other operational statistics.

> **Security note:** Never commit the real admin password to GitHub, README files, screenshots, or public documentation.

---

## Technology Stack

### Backend
- PHP 8+
- PDO
- MySQL / MariaDB

### Frontend
- HTML5
- CSS3
- JavaScript
- Responsive layout and reusable UI components

### Development Environment
- XAMPP
- Apache
- MySQL / MariaDB

### Email and Authentication
- Brevo Transactional Email API
- HTTPS API requests over port 443
- OTP-based email verification

### AI Tools
- **Manus AI** for AI-assisted development, debugging, refinement, and project support
- **xAI / Grok** for AI-assisted customer support and development-related tasks
- **MobiSaathi** as the integrated customer-facing AI support feature

---

## XAMPP Installation

### 1. Extract the project

Extract the submission ZIP and place the project inside:

```text
C:\xampp\htdocs\masha-allah-mobile-shop\
```

The main entry point should be:

```text
C:\xampp\htdocs\masha-allah-mobile-shop\index.php
```

### 2. Start XAMPP

Start:

- Apache
- MySQL

### 3. Database configuration

Check:

```text
config/database.php
```

Typical XAMPP defaults are:

```text
DB_HOST=127.0.0.1
DB_NAME=masha_allah_shop
DB_USER=root
DB_PASS=
DB_PORT=3306
```

If your local XAMPP installation uses different credentials, configure the corresponding environment variables:

```text
DB_HOST
DB_NAME
DB_USER
DB_PASS
DB_PORT
```

### 4. Open the website

Visit:

```text
http://localhost/masha-allah-mobile-shop/
```

On a fresh installation, the application can initialize the configured database, apply the schema, and seed the supplied product catalogue.

The demonstration catalogue contains **48 products**.

### 5. Initial administrator setup

If the project includes `setup.php` and your local installation requires first-time owner setup:

1. Temporarily set:

```text
ALLOW_INITIAL_SETUP=1
```

2. Open:

```text
http://localhost/masha-allah-mobile-shop/setup.php
```

3. Create the administrator account.
4. Set:

```text
ALLOW_INITIAL_SETUP=0
```

5. Remove or disable `setup.php` after initial setup.

The setup page should not remain publicly accessible after initialization.

---

## Email OTP Configuration

The production application uses the **Brevo HTTPS Transactional Email API**, not direct SMTP socket connections.

Configure these environment variables:

```text
BREVO_API_KEY
SMTP_FROM_EMAIL
SMTP_FROM_NAME
```

Example:

```text
BREVO_API_KEY=your_brevo_api_key
SMTP_FROM_EMAIL=your_verified_sender@example.com
SMTP_FROM_NAME=Masha Allah Mobile & EasyPaisa Shop
```

### Important

- Use a **Brevo API key**, not an SMTP key.
- The sender email must be verified in Brevo.
- Never commit the API key to GitHub.
- Never place real API credentials inside the project ZIP.
- Rotate/revoke any credential that has been exposed publicly.

---

## Database

The application uses MySQL/MariaDB with PDO and relational tables for the site's core data.

The database supports areas including:

- Users
- Products
- Categories
- Services
- Inventory
- Orders
- Sales
- Messages / enquiries
- Related operational records

The demonstration environment contains **48 products** and an administrator account.

---

## Security Features

The project implements multiple security controls, including:

- PDO prepared statements
- `password_hash()`
- `password_verify()`
- CSRF tokens
- Session regeneration
- Output escaping
- Admin role/authorization checks
- OTP email verification
- OTP resend cooldown
- OTP request rate limiting
- File upload validation
- MIME-type whitelisting
- File-size validation
- Randomized uploaded filenames
- Protection of configuration files
- Protection against direct access to sensitive files where supported by Apache configuration

For production use, HTTPS should be enabled and application host configuration should be set correctly.

---

## Project Structure

A simplified structure is:

```text
masha-allah-mobile-shop/
│
├── index.php
├── about.php
├── products.php
├── services.php
├── contact.php
├── login.php
├── signup.php
├── verify.php
│
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── products.php
│   ├── categories.php
│   ├── services.php
│   ├── inventory.php
│   ├── users.php
│   ├── orders.php
│   ├── sales.php
│   ├── messages.php
│   └── account.php
│
├── config/
│   ├── database.php
│   └── email.php
│
├── includes/
│   ├── functions.php
│   ├── email.php
│   ├── admin_guard.php
│   ├── header.php
│   └── footer.php
│
├── assets/
├── sql/
└── ...
```

---

## AI-Assisted Development

AI tools were used throughout the development process for:

- Code generation and refinement
- Debugging
- Error analysis
- UI/UX improvement
- Content generation
- Deployment troubleshooting
- Customer-support functionality

### AI Tools Used

**Manus AI**
- Development assistance
- Code refinement
- Debugging support
- Project troubleshooting

**xAI / Grok**
- AI-assisted development
- Customer-support assistance
- MobiSaathi functionality

---

## Deployment

The production application is hosted on **Railway**.

### Production URL

https://mashaallahmobile.up.railway.app/

The deployed environment runs PHP and serves the application through Railway's public networking.

The project also uses Brevo's HTTPS API for transactional OTP email delivery.

---

## Submission Deliverables

The final assessment package includes:

- Public live website
- GitHub source repository
- Database SQL files
- Three-page project report
- Three-to-five minute demo video
- Working customer signup and OTP verification
- Working customer login
- Working administrator portal

---

## Demonstration Flow

For a project evaluation/demo, the recommended order is:

1. Open the public Home page
2. Show About
3. Browse Products
4. Browse Services
5. Show Contact page
6. Demonstrate customer Sign Up
7. Show OTP email
8. Verify the customer account
9. Sign in as customer
10. Open Admin Login
11. Show Admin Dashboard
12. Demonstrate Products/Services/Inventory management

---

## Final Notes

This project is intended as a complete classroom/assessment submission and demonstration application.

For production deployment:

- Keep secrets in environment variables.
- Never publish API keys or passwords.
- Keep setup/recovery scripts disabled after initialization.
- Use HTTPS.
- Review database credentials and access permissions.
- Review file upload and administrative access controls regularly.

**Project:** Masha Allah Mobile & EasyPaisa Shop  
**Developer:** Ghulam Murtaza
