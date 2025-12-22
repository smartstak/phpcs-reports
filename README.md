# Plugin Report

This repository documents multiple issues identified across several WordPress plugins during functional testing, code review, and compatibility checks. The objective of this report is to provide a clear and structured overview of issues so they can be reviewed and resolved efficiently.

---

## Affected Plugins

The following plugins are covered under this report:

- `SearchFilterSort`
- `WPSyncSheets Lite For Contact Form 7`
- `WPSyncSheets Lite For Elementor`
- `WPSyncSheets Lite For Gravity Forms`
- `WPSyncSheets Lite For WooCommerce`
- `WPSyncSheets Lite For WPForms`

---

## Testing Environment

  - **Tool:** `Local WP`
  - **WordPress:** `6.9`
  - **PHP:** `8.4.10`
  - **MySQL:** `8.0.35`
  - **Server:** `Nginx`
  - **Browser(s):** `Chrome, Firefox, Safari`
  - **PHP Min Tested Version:** `7.4`

---

## Summary of Issues

| ID | Bug Title
|----|----------------------------
| #1 | All `wpsyncsheets` plugins' API integration form fields must be REQUIRED. Right now it saves empty form. 
|    | `File:` plugin-folder/includes/class-prefix-plugin-settings.php
| #2 | `SearchFilterSort` plugin Filter form's Categories section missing li tags.
|    | `File:` searchfiltersort/templates/frontend/filters.php
| #3 | Rest of errors, warning are reported in PHPCS report file in each plugin folder.

---

## Features Implemented

| ID | Feature
|----|----------------------------
| #1 | - PSR-4 Autloading implemented with Composer for `SearchFilterSort` plugin. No need to include class files manually.
| #2 | - Built assets [css, js] files with webpack build tool using wordpress/scripts and other dependencies.
| #3 | - Implemented `PREFIX_Assets_URL` class for all plugins to load assets according to
|    | - CONSTANT `PREFIX_ENVIRONMENT`. Value can be`development` or `production` environment.
|    | - File: `class-wpsslwp-assets-url.php`

---