# AGENTS.md — WooCommerce Payment Plugin

Guidance for AI coding agents working on this repository.

---

## 1. Project Overview

**Yoco WooCommerce Payment Plugin** (`yoco-payment-gateway`) is a WordPress/WooCommerce plugin that adds Yoco Online as a payment gateway for WooCommerce stores. It is distributed via the WordPress Plugin Directory.

It sits between **WooCommerce checkout** (upstream) and the **Yoco Online API** (downstream).

Key responsibilities:

- Register a WooCommerce payment gateway (`Yoco` option in checkout)
- Handle payment initiation, redirect, and confirmation flows with Yoco Online
- Process webhooks from Yoco to update order status
- Support full and partial refunds via the WooCommerce admin
- Manage plugin settings (API keys, environment, appearance)

---

## 2. Tech Stack

| Concern        | Technology                                 |
|----------------|--------------------------------------------|
| Language       | PHP 8.2                                    |
| Platform       | WordPress + WooCommerce 8+                 |
| Package manager| Composer (PHP) + npm (Node tooling)        |
| Static analysis| PHPStan 1.10 (level 5)                     |
| E2E tests      | Playwright 1.58.2 + `@wordpress/env` 9.10  |
| E2E reporting  | Allure 2.15.1                              |
| i18n           | `node-wp-i18n` (POT file generation)       |
| Build/package  | `composer archive` (ZIP for distribution)  |
| CI             | GitLab CI + SonarCloud                     |

---

## 3. Dev Environment Setup

### Prerequisites

- PHP 8.2 + Composer
- Node.js >=25.0.0 + npm
- Docker (for `@wordpress/env` local WordPress environment)
- WP-CLI (installed via `@wordpress/env`)

### Install dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies (E2E test tooling)
npm install
```

### Start local WordPress environment

```bash
npm run start
# or
npm run wp-env -- start
```

This starts a WordPress + WooCommerce environment via Docker on port **8086** (test site).

### Stop environment

```bash
npm run destroy
```

---

## 4. Build Commands

```bash
# Generate i18n POT file
npm run makepot

# Archive plugin for distribution (composer archive → ZIP)
npm run archive

# Full build: POT file + archive
npm run build
```

The build output is a ZIP file suitable for WordPress Plugin Directory submission.

---

## 5. Test Commands

```bash
# Run E2E tests (Playwright, requires running WordPress environment)
npm run test-e2e

# Run E2E tests in UI mode (Playwright inspector)
npm run test-e2e-ui

# Static analysis (PHPStan)
npm run analyse
# or
vendor/bin/phpstan analyse
```

E2E tests require:
1. A running `@wordpress/env` environment (`npm run start`)
2. A publicly accessible tunnel for Yoco webhook callbacks: `npm run tunnel`
3. Yoco test API keys set as environment variables (see `playwright.config.js`)

---

## 6. Lint / Format Commands

```bash
# PHPStan static analysis (level 5)
vendor/bin/phpstan analyse

# Or via npm
npm run analyse
```

There is no Prettier or PHP-CS-Fixer configuration. Code style is maintained by convention following WordPress coding standards.

---

## 7. Code Style & Conventions

### PHP namespace

All classes use the `Yoco\` namespace root.

### Directory layout

```
src/
  Core/          ← Plugin bootstrap and registration
  Cron/          ← Background job scheduling
  Gateway/
    Admin/       ← WooCommerce settings page
    Checkout/    ← Checkout integration hooks
    Models/      ← Data models
    Payment/     ← Payment initiation and flow
    Processors/  ← Payment processor adapters
    Refund/      ← Refund handling
  Helpers/
    Admin/       ← Admin UI helpers
    Http/        ← HTTP client wrappers
    Money/       ← Currency/amount utilities
    Security/    ← HMAC signature validation
    Storage/     ← Meta storage helpers
    Validation/  ← Input validation
  Installation/  ← Plugin activation and schema setup
  Integrations/
    Webhook/     ← Incoming Yoco webhook handler
    Yoco/        ← Yoco API client
  Repositories/  ← Data access layer
  Telemetry/     ← Analytics/tracking
```

### WooCommerce patterns

- Hook into WooCommerce via WordPress actions/filters — avoid direct method calls across plugin boundaries
- The main gateway class extends `WC_Payment_Gateway`
- Admin settings are registered via `init_form_fields()` in the gateway class
- Order meta is stored via `WC_Order::update_meta_data()` / `get_meta()`

### Static analysis

PHPStan is configured at **level 5** (`phpstan.neon`) and uses WooCommerce stubs. New code must pass static analysis. Run before every commit.

### Naming

- PHP classes: `PascalCase` under `Yoco\<Module>` namespace
- WordPress hooks: `yoco_<action_name>` prefix
- Order meta keys: `_yoco_<field_name>` prefix (leading underscore = hidden in admin)

---

## 8. Project Structure

```
woocommerce-payment-plugin/
├── src/                           ← Main PHP source (PSR-4 autoloaded)
├── inc/                           ← Legacy PHP helpers
├── assets/
│   ├── images/                    ← Plugin images
│   ├── lang/                      ← i18n translation files
│   ├── scripts/                   ← Frontend JavaScript
│   └── styles/                    ← Frontend CSS
├── tests/
│   └── e2e-pw/                    ← Playwright E2E tests
├── scripts/                       ← Build/release scripts
├── composer.json                  ← PHP dependencies + archive config
├── package.json                   ← Node tooling dependencies
├── phpstan.neon                   ← Static analysis config (level 5)
├── playwright.config.js           ← E2E test config (90s timeout, Chrome only)
├── .wp-env.json                   ← WordPress dev environment config (PHP 8.2, port 8086)
├── yoco_wc_payment_gateway.php    ← Plugin entry point
├── readme.txt                     ← WordPress Plugin Directory readme
└── .gitlab-ci.yml                 ← CI (SonarCloud + WordPress.org SVN publish)
```

---

## 9. PR / Commit Guidelines

### Version bumping

When releasing a new version, update the version number in **all** of these files (see README for the full list):
- `yoco_wc_payment_gateway.php` (plugin header + `$version` constant)
- `readme.txt` (Stable tag)
- `composer.json` (version field)

### Branch naming

- Feature: `feature/<short-description>`
- Fix: `fix/<short-description>`

### Commit messages

```
<type>: <short description>
```

Types: `feat`, `fix`, `chore`, `refactor`, `docs`.

### CI / CD pipeline

- **MR:** SonarCloud analysis
- **Tags:** Manual `wordpress` job publishes to WordPress.org via SVN
- Workflow runs for MRs, main branch commits, and tags

### WordPress.org publishing

The `wordpress` CI job uses SVN to push releases to the WordPress Plugin Directory. This is triggered manually on tagged releases. Credentials are stored as CI variables.

---

## 10. Security Considerations

**Never commit or log the following:**

- `YOCO_PUBLIC_KEY` / `YOCO_SECRET_KEY` — merchant API keys
- WordPress database credentials
- WordPress.org SVN credentials (`SVN_USERNAME`, `SVN_PASSWORD` in CI)
- Yoco webhook signing secrets

**Input validation:**

- All incoming webhook payloads must be verified using HMAC signatures (`Helpers/Security/`)
- Sanitize all `$_POST`/`$_GET` data using WordPress sanitization functions before use
- Escape all output with `esc_html()`, `esc_attr()` etc. to prevent XSS

**WooCommerce nonces:**

- All form submissions and AJAX handlers must verify WordPress nonces
- Never process order state changes without verifying the nonce and user permissions

---

## 11. Gotchas & Known Quirks

### Tunnel required for E2E tests

Yoco's webhooks need to reach your local environment. The `npm run tunnel` command sets up a `localtunnel` to expose your local WordPress environment. The tunnel URL must be configured as the webhook URL in the Yoco merchant dashboard.

### WordPress environment takes time to start

`npm run start` / `@wordpress/env start` pulls Docker images and runs WP-CLI setup scripts. First start can take several minutes.

### PHPStan bootstrap required

PHPStan uses `php-stubs/woocommerce-stubs` and WordPress stubs. These are loaded via `phpstan.neon` bootstrap files. Running PHPStan without Composer dependencies installed will fail.

### Version must be updated in multiple files

Unlike most projects, the plugin version is declared in several places (plugin header, readme.txt, composer.json). Updating only one will cause inconsistencies. Use the release script in `scripts/` if available.

### Archive excludes vendor and test files

`composer.json` configures `composer archive` to exclude `vendor/`, `tests/`, `.git`, and other dev files from the distribution ZIP. The archive is what gets submitted to WordPress.org.
