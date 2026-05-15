=== ezy E Invoice ===
Contributors:      abssaiful
Tags:              invoice, billing, pdf, woocommerce, email
Requires at least: 5.8
Tested up to:      6.9
Requires PHP:      7.4
Stable tag: 2.2.1
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Donate link:       https://ehut.tech/donate/

A modern and easy e-invoice plugin – create, send and manage professional PDF invoices directly from your WordPress admin.

== Description ==

**ezy E Invoice** makes creating and sending professional e-invoices effortless. Build your client list, link your products (with optional WooCommerce sync), and fire off a branded PDF invoice by email in just a few clicks.

= Key Features =

* **Client Management** – Store unlimited clients with name, company, email, phone, address and notes. Quick-search autocomplete when creating invoices.
* **Product/Service Catalogue** – Maintain a product list with price and description. One-click WooCommerce sync imports your existing store products.
* **Smart Invoice Builder** – Select a client, add line items with quantity and unit price, and watch the totals update live. Supports discount, tax and service charge.
* **PDF Generation** – Every invoice is rendered as a professionally styled PDF using FPDF – no third-party SaaS required.
* **Automatic Email Delivery** – The moment you click *Create & Send*, the client receives a branded HTML email with the PDF attached.
* **Invoice Management** – List, filter, resend, mark as paid, download or delete invoices from a clean admin table.
* **Flexible Settings** – Configure company branding, invoice prefix & numbering, payment terms, bank/payment details, tax label & rate, service charge, discount toggle, currency, email sender details, CC/BCC and custom footer notes.
* **WooCommerce Integration** – Optionally sync WooCommerce products into the ezy E Invoice product catalogue with a single button.

= Use Cases =

* Freelancers billing clients for services
* Small businesses issuing recurring invoices
* WooCommerce store owners needing a simple invoicing module outside of orders

= Languages =

Ready for translation (`.pot` file included). All strings are wrapped in `__()` / `esc_html__()` with the `ezy-e-invoice` text domain.

== Installation ==

**Automatic (recommended)**

1. Go to **Plugins → Add New** in your WordPress admin.
2. Search for **ezy E Invoice**.
3. Click **Install Now** then **Activate**.

**Manual**

1. Download the ZIP file.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and click **Install Now**, then **Activate**.

**First-Time Setup**

1. Navigate to **ezy E Invoice → Settings**.
2. Fill in your **Company Details** (name, logo, address, registration numbers).
3. Configure **Invoice Settings** (prefix, starting number, currency, payment terms).
4. Set **Tax & Charges** (GST/VAT/SST rate, service charge, discount options).
5. Configure **Email Settings** (from name, from address, subject, body template).
6. Save, then add your clients and products — you're ready to invoice!

== Frequently Asked Questions ==

= Does this plugin work without WooCommerce? =
Yes. WooCommerce is completely optional. You can manually add products/services to the ezy E Invoice catalogue without WooCommerce installed.

= How are PDFs generated? =
PDFs are generated server-side using the FPDF library (bundled). No external service is required.

= What email service does the plugin use? =
It uses WordPress's built-in `wp_mail()` function, so it works with any SMTP plugin (e.g. WP Mail SMTP, FluentSMTP).

= Can I customise the invoice PDF design? =
The PDF template is in `templates/invoice-pdf-html.php` and the generator class is in `includes/class-ezy-pdf.php`. Developers can override these to suit their brand.

= Where are invoices stored? =
Invoices are stored in custom database tables created on activation. No post types are used, keeping your WordPress database clean.

= Can I resend an invoice? =
Yes. From **ezy E Invoice → Invoices**, click **Resend** on any invoice row to re-email the PDF to the client.

= Is there a bulk delete option? =
Bulk actions are on the roadmap for v2.2. For now, individual delete is available on each invoice row.

= What happens if I uninstall the plugin? =
The `uninstall.php` file removes all plugin options. Database tables are retained by default to protect your data. You can manually drop them if needed.

== Screenshots ==

1. **Dashboard** – Quick-glance stats: total invoices, revenue, pending and paid counts.
2. **Settings – Company Details** – Enter your brand name, logo, address and registration numbers.
3. **Settings – Invoice Config** – Invoice prefix, numbering, currency and payment terms.
4. **Settings – Tax & Charges** – Enable/disable tax, service charge and discount with custom labels and rates.
5. **Settings – Email Settings** – Sender name, address, subject, body template and CC/BCC.
6. **Clients List** – Searchable, paginated client table with inline edit and delete.
7. **Add / Edit Client** – Modal form with all client fields.
8. **Products List** – Product catalogue with WooCommerce sync button.
9. **Create Invoice** – Step-by-step builder: client autocomplete, dynamic line items, live totals.
10. **Invoice List** – Filter by status (Draft / Sent / Paid / Overdue), search and bulk actions.
11. **View Invoice** – Full invoice preview with download PDF and resend buttons.
12. **PDF Invoice** – Sample of the generated PDF sent to clients.

== Changelog ==

= 2.2.1 =
* Fix: Added phpcs:ignore with justification on read-only GET parameter — no nonce needed on display-only admin pages.
* Fix: Invoice view page now accessible via direct URL or bookmark (no nonce expiry issue).

= 2.2.0 =
* Security: Replaced deprecated utf8_decode() with mb_convert_encoding() in PDF generator.
* Security: Explicit sanitization of all $_POST fields in client and product save handlers.
* Security: Added wp_unslash() to json_decode() item fields in invoice handler.
* Security: Nonce verification added to filter/pagination links on invoices list page.
* Improvement: Plugin URI updated to GitHub repository.

= 2.1.9 =
* Fix: Added ABSPATH guard and phpcs:disable to all FPDF font PHP files (third-party library).
* Fix: Replaced readfile() with WP_Filesystem()->get_contents() for PDF delivery.
* Fix: Removed all error_log() debug calls from PDF class.

= 2.1.8 =
* Fix: PDF is always regenerated fresh on download — no more stale cached layout served.

= 2.1.7 =
* Fix: Items table no longer overlaps Bill To section when client has many address lines.

= 2.1.6 =
* Fix: FPDF font path now uses DIRECTORY_SEPARATOR — fixes font loading on Windows (Laragon/WAMP/XAMPP).

= 2.1.5 =
* Fix: All strings converted via iconv() before passing to FPDF — resolves UTF-8 encoding errors.
* Fix: Logo image load wrapped in try/catch — skipped gracefully on failure.

= 2.1.4 =
* Fix: PDF download now uses secure admin_init handler with nonce — forces browser download instead of opening in tab.

= 2.1.3 =
* Fix: Moved phpcs:disable to line 2 in all view/template files for guaranteed PHPCS coverage.

= 2.1.2 =
* Refactor: All class/constant/option/menu prefixes updated to EZYEIN_ for WordPress.org compliance.
* Fix: Replaced fragile ABSPATH path detection with wp_upload_dir().
