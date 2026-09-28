# Kobo Choice - E-Commerce Web Application

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Payment](https://img.shields.io/badge/Payment-Paystack-0BA4DB)](https://paystack.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A modern, full-featured, responsive e-commerce web application built with PHP and MySQL. Designed for modern online storefronts, featuring full catalog management, customer authentication, wishlists, shopping cart, Paystack payment processing, transactional emails, and an administrative dashboard with CMS banner controls.

---

## 🚀 Key Features

### 🛍️ Storefront & Shopping Experience
- **Responsive Design**: Mobile-first layout optimized for smartphones, tablets, and desktops.
- **Product Catalog**: Multi-category browsing, search, price/category filters, and sorting.
- **Interactive Details**: Image galleries, related items, product specifications, and stock indicators.
- **Shopping Cart & Wishlist**: Persistent cart sessions and customer wishlist management.
- **Coupons & Discounts**: Discount coupon code redemption during checkout.

### 💳 Checkout & Payments
- **Paystack Integration**: Secure online payments via debit cards, bank transfers, and USSD.
- **Order Tracking & Receipts**: Automatic order generation, reference tracking, and printable/downloadable PDF receipts.
- **Webhook Verification**: Secure server-side signature verification for Paystack transactions.

### 👤 Customer Account Management
- **Authentication**: Email/password registration and login with secure password hashing (`PASSWORD_DEFAULT`).
- **Google OAuth**: One-click "Sign in with Google" integration.
- **Password Reset Flow**: Deliverability-focused 6-digit email verification codes with rate limiting and 10-minute expiry.
- **Customer Dashboard**: Manage profile details, shipping/billing addresses, and review past orders.

### 🛡️ Admin Dashboard & CMS
- **Catalog Management**: Create, edit, and organize products, categories, and inventory.
- **Order Management**: View customer orders, update statuses (pending, processing, shipped, delivered, cancelled), and mark seen/unseen items.
- **Visual CMS Banners**: Manage homepage hero sliders, promo pairs, ad banners, and promotional grids.
- **Audit Logs**: Track administrative actions and system events.

### 🔒 Security & Performance
- **Database Safety**: 100% prepared SQL statements with PDO singleton.
- **Upload Hardening**: Dedicated `.htaccess` rules preventing script execution in upload directories.
- **CSRF & XSS Protection**: Form tokens and input sanitization across customer and admin actions.
- **Environment Isolation**: Decoupled secrets using `.env` (never committed to version control).

---

## 📋 Requirements

- **PHP**: 8.1 or higher
- **PHP Extensions**:
  - `pdo_mysql`
  - `curl`
  - `mbstring`
  - `openssl`
  - `gd`
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache with `mod_rewrite` enabled (e.g., XAMPP, WAMP, LAMP)
- **Composer**: Dependency manager for PHP

---

## 🛠️ Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/KoboChoice-Ecommerce.git
cd KoboChoice-Ecommerce
```

### 2. Install PHP Dependencies
Install the required third-party packages (PHPMailer, Dompdf) via Composer:
```bash
composer install
```

### 3. Environment Configuration
Copy the template configuration file to `.env`:
```bash
cp .env.example .env
```
Open `.env` in your text editor and set your local credentials:
```env
# Application URL
BASE_URL=/myweb/kobostoresecommerce
SITE_URL=http://localhost/myweb/kobostoresecommerce

# Database Credentials
DB_HOST=localhost
DB_NAME=kobo_stores
DB_USER=root
DB_PASS=

# Paystack API Keys (Optional - Test keys from Paystack dashboard)
PAYSTACK_PUBLIC_KEY=pk_test_...
PAYSTACK_SECRET_KEY=sk_test_...
PAYSTACK_CURRENCY=NGN

# SMTP Mail Settings (For password reset & order confirmations)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_FROM_EMAIL=your_email@gmail.com
SMTP_FROM_NAME="Kobo Choice"
```

### 4. Database Setup
1. Create a MySQL database (e.g. `kobo_stores`).
2. Import the schema and seed data from `db/kobo_stores.sql`:
```bash
mysql -u root -p kobo_stores < db/kobo_stores.sql
```
*(Optional)* If applicable, apply additional schema updates from the `db/` folder:
- `db/migration_order_seen.sql`
- `db/migration_product_section.sql`
- `db/seed_products.sql`

### 5. Web Server Configuration
Ensure your Apache server has `mod_rewrite` enabled:
- If hosted in a subdirectory (e.g. XAMPP `htdocs/myweb/kobostoresecommerce`), confirm `BASE_URL` in `.env` and `RewriteBase` in `.htaccess` match your local directory name.
- If hosted at a domain root (e.g. `https://kobochoice.com`), set:
  ```env
  BASE_URL=
  SITE_URL=https://kobochoice.com
  ```
  and update `.htaccess` `RewriteBase /`.

### 6. Default Admin Login
After importing the database seed, access the admin panel at:
- **URL**: `http://localhost/myweb/kobostoresecommerce/admin`
- **Email**: `admin@kobostores.shop`
- **Password**: `Password2$`

*(Be sure to change this default password upon initial login in production).*

---

## 📁 Directory Structure

```text
KoboChoice-Ecommerce/
├── admin/               # Admin panel controllers, views, and assets
├── api/                 # Backend RESTful JSON endpoints (cart, orders, payment)
├── auth/                # Authentication handlers (Google OAuth, login, register)
├── config/              # Application configuration & autoloader
│   ├── autoload.php     # Class autoloading
│   ├── config.php       # Constants & environment variable loader
│   └── database.php     # PDO Database Singleton
├── controllers/         # MVC Controllers
├── css/                 # Core stylesheets and vendor CSS
├── db/                  # SQL database schemas, migrations, and seed files
├── fonts/               # Web font assets
├── images/              # Theme static imagery, logos, and banners
├── includes/            # Common layout components, auth helpers, mailer
├── js/                  # Frontend scripts, AJAX handlers, and UI libraries
├── models/              # Data models (Product, Order, Customer, etc.)
├── public/              # Public asset folder
│   └── uploads/         # Hardened runtime upload directory (avatars, products)
├── views/               # Front-facing PHP views and page templates
├── .env.example         # Example environment configuration template
├── .gitignore           # Git ignore definitions
├── .htaccess            # Apache URL rewrite and security rules
├── composer.json        # PHP dependencies
└── README.md            # Project documentation
```

---

## 🔒 Security Best Practices

1. **Keep `.env` Private**: Never push `.env` or commit real API keys/credentials to version control.
2. **Hardened Uploads**: The `public/uploads/.htaccess` prevents PHP execution inside the media folder to neutralize file upload attack vectors.
3. **Prepared Statements**: All database operations use PDO prepared queries to mitigate SQL injection.
4. **App Passwords**: When using Gmail for SMTP, always generate a dedicated Google App Password rather than your account's primary password.

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).
