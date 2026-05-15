=== ezy E Invoice ===
Contributors:      abssaiful
Tags:              invoice, billing, pdf, woocommerce, email
Requires at least: 5.8
Tested up to:      6.9
Requires PHP:      7.4
Stable tag: 2.2.0
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

= 2.1.8 =
* Fix: PDF is always regenerated fresh on download — no more stale cached layout served.
* Fix: Items table no longer overlaps Bill To section when client has many address lines.

= 2.1.7 =
* Fix: Product table no longer overlaps the Bill To section when client has many address lines.

= 2.1.6 =
* Fix: FPDF font path now uses DIRECTORY_SEPARATOR — fixes font loading on Windows (Laragon/WAMP/XAMPP)

= 2.1.8 =
* Fix: PDF is always regenerated fresh on download — no more stale cached layout served.
* Fix: Items table no longer overlaps Bill To section when client has many address lines.

= 2.1.7 =
* Fix: Product table no longer overlaps the Bill To section when client has many address lines.

= 2.1.6 =
* Fix: PDF generation failure for UTF-8/non-Latin characters in company or client names (iconv encoding fix)
* Fix: Logo image errors no longer abort PDF — logo is gracefully skipped with error log entry
* Fix: PDF download now shows a clear error message instead of silently serving HTML
* Fix: Switched to Output('S') + file_put_contents for more reliable PDF writing

= 2.1.8 =
* Fix: PDF is always regenerated fresh on download — no more stale cached layout served.
* Fix: Items table no longer overlaps Bill To section when client has many address lines.

= 2.1.7 =
* Fix: Product table no longer overlaps the Bill To section when client has many address lines.

= 2.1.6 =
* Fix: Download PDF button now forces file download instead of opening in browser tab
* Fix: Auto-regenerate PDF if previously saved as HTML fallback (FPDF class name mismatch on old installs)
* Fix: Removed dependency on pdf_path file existence check — download always works via secure handler

= 2.1.3 =
* Fix: Renamed all plugin class prefixes from `Ezy_` to `EZYEIN_` to meet WordPress.org 4-character prefix requirement
* Fix: Renamed FPDF library class from `FPDF` to `EZYEIN_FPDF` to prevent conflicts with other plugins using FPDF
* Fix: Renamed all plugin constants from `EZY_INVOICE_*` to `EZYEIN_*` (e.g. `EZYEIN_VERSION`, `EZYEIN_DIR`, `EZYEIN_URL`)
* Fix: Renamed all option keys from `ezy_invoice_*` to `ezyein_*` to meet prefix requirements
* Fix: Renamed admin menu slugs from `ezy-*` to `ezyein-*`
* Fix: Renamed JS script handle and localized object from `ezy-invoice-admin` / `ezyInvoice` to `ezyein-admin` / `ezyeinInvoice`
* Fix: Replaced fragile `str_replace(site_url, ABSPATH, $url)` path detection with `wp_upload_dir()` for reliable image path resolution in PDF
* Fix: Improved FPDF binary output handling with proper inline documentation
* Fix: Added inline documentation for standalone HTML template used in PDF generation



= 2.1.1 – 2025-04-13 =
* Initial public release on WordPress.org.
* Client management with AJAX autocomplete.
* Product catalogue with WooCommerce sync.
* PDF invoice generation via bundled FPDF library.
* Automatic email with PDF attachment on invoice creation.
* Four-tab settings panel: Company, Invoice, Tax & Charges, Email.
* Invoice list with Draft / Sent / Paid / Overdue statuses.
* Resend, mark-paid and delete actions on invoices.

== Upgrade Notice ==

= 2.1.1 =
Initial release. Install fresh — no upgrade steps required.
