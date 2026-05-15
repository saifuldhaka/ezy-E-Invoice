## [2.1.6] - 2026-05-09
### Fixed
- FPDF font path uses PHP DIRECTORY_SEPARATOR instead of hardcoded forward slash — fixes font not found error on Windows servers (Laragon, WAMP, XAMPP)

## [2.1.6] - 2026-05-09
### Fixed
- UTF-8 / non-Latin characters in company/client data now handled correctly via iconv (FPDF uses Latin-1)
- Logo image errors no longer abort the entire PDF generation
- PDF download shows descriptive error instead of serving HTML fallback
- Use Output('S') + file_put_contents for reliable PDF file writing

## [2.1.3] - 2026-05-04

### Fixed
- Renamed all plugin class prefixes `Ezy_*` → `EZYEIN_*` to satisfy WordPress.org 4-character prefix requirement
- Renamed bundled FPDF library class `FPDF` → `EZYEIN_FPDF` to avoid conflicts with other plugins
- Renamed plugin constants `EZY_INVOICE_*` → `EZYEIN_*`
- Renamed all database option keys `ezy_invoice_*` → `ezyein_*`
- Renamed admin menu slugs `ezy-*` → `ezyein-*`
- Renamed JS script handle/object `ezy-invoice-admin`/`ezyInvoice` → `ezyein-admin`/`ezyeinInvoice`
- Replaced fragile ABSPATH path detection with `wp_upload_dir()` in PDF generator
- Improved FPDF binary output and HTML template inline documentation

# Changelog

## [2.2.0] - 2025-05-15

### Fixed
- Replaced deprecated `utf8_decode()` with `mb_convert_encoding()` in PDF generator.
- Added proper nonce verification to all admin GET-param view pages (`page-invoices.php`, `page-view-invoice.php`, `page-create-invoice.php`).
- Explicitly sanitize all `$_POST` fields in `ajax_save()` for clients and products before passing to DB layer.
- Added `wp_unslash()` to `json_decode()` item fields in invoice handler.
- Moved `phpcs:ignore` annotation to the same line as binary PDF `echo` statement.

## [2.1.6] - 2026-05-09
### Fixed
- Download PDF button now forces file download (Content-Disposition: attachment) instead of opening in browser
- Auto-regenerate PDF if stored path was an HTML fallback from FPDF class mismatch on older installs
- Secure nonce-verified download handler via admin_init

All notable changes to **ezy E Invoice** are documented here.

## [2.1.1] – 2025-04-13
### Added
- Initial public release
- Client management (add / edit / delete) with AJAX autocomplete search
- Product / service catalogue with optional WooCommerce sync
- Invoice builder with dynamic line items and live subtotal / tax / total calculation
- PDF generation via bundled FPDF library (no SaaS dependency)
- Automatic email delivery on invoice creation with PDF attached
- Invoice statuses: Draft, Sent, Paid, Overdue
- Invoice actions: View, Resend, Mark as Paid, Download PDF, Delete
- Four-tab settings panel:
  - Company Details (name, logo URL, address, tax reg, company reg)
  - Invoice Config (prefix, next number, padding, currency, payment terms, bank details)
  - Tax & Charges (tax label/rate, service charge label/rate, discount toggle)
  - Email Settings (from name, from address, subject, HTML body, CC, BCC)
- Translation-ready with `.pot` file and `ezy-e-invoice` text domain
- `uninstall.php` to clean plugin options on removal
