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
