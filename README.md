# CRA Compliance API

![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.4-blue)
![Symfony](https://img.shields.io/badge/Symfony-8.0-black)

A free REST API to check WordPress plugins and Rust crates for
EU Cyber Resilience Act (CRA) compliance.

## Live API

Base URL: `https://web-production-8ad02.up.railway.app`

Try it now:
- https://web-production-8ad02.up.railway.app/api/status
- https://web-production-8ad02.up.railway.app/api/check/plugin/woocommerce
- https://web-production-8ad02.up.railway.app/api/check/crate/tokio

**CRA deadline: September 11, 2026**

## Endpoints

### Check API status
GET /api/status

### Check WordPress plugin
GET /api/check/plugin/{slug}

Example: /api/check/plugin/woocommerce

### Check Rust crate
GET /api/check/crate/{name}

Example: /api/check/crate/tokio

## Response example

{
  "slug": "woocommerce",
  "name": "WooCommerce",
  "version": "10.6.2",
  "last_updated": "2026-03-31 10:28am GMT",
  "found": true,
  "cra_status": "ok",
  "issues": [],
  "checked_at": "2026-04-14T10:17:21+00:00"
}

## CRA Status values

- ok      — no issues found
- warning — not updated in 6+ months
- risk    — not updated in 12+ months
- unknown — cannot determine status

## Tech stack

- PHP 8.4
- Symfony 8.0
- REST API

## Author

gritzon (https://github.com/gritzon)
