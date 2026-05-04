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
