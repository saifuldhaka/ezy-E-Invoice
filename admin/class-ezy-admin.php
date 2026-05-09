<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class EZYEIN_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menus'  ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets'  ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_donate_banner' ] );
        add_action( 'in_admin_header',       [ $this, 'render_donate_banner'  ] );
        EZYEIN_Settings::register_hooks();
        add_action( 'admin_init', [ $this, 'handle_pdf_download' ] );
    }

    public function register_menus() {
        add_menu_page(
            'ezy E Invoice', 'ezy E Invoice',
            'manage_options', 'ezyein-dashboard',
            [ $this, 'page_dashboard' ],
            'dashicons-media-document', 30
        );
        add_submenu_page( 'ezyein-dashboard', 'Dashboard',      'Dashboard',      'manage_options', 'ezyein-dashboard',         [ $this, 'page_dashboard'      ] );
        add_submenu_page( 'ezyein-dashboard', 'Clients',        'Clients',        'manage_options', 'ezyein-clients',         [ $this, 'page_clients'        ] );
        add_submenu_page( 'ezyein-dashboard', 'Products',       'Products',       'manage_options', 'ezyein-products',        [ $this, 'page_products'       ] );
        add_submenu_page( 'ezyein-dashboard', 'Invoices',       'All Invoices',   'manage_options', 'ezyein-invoices',        [ $this, 'page_invoices'       ] );
        add_submenu_page( 'ezyein-dashboard', 'Create Invoice', 'Create Invoice', 'manage_options', 'ezyein-create-invoice',  [ $this, 'page_create_invoice' ] );
        add_submenu_page( 'ezyein-dashboard', 'Settings',       'Settings',       'manage_options', 'ezyein-settings',        [ $this, 'page_settings'       ] );
        // Hidden view page
        add_submenu_page( null, 'View Invoice', 'View Invoice', 'manage_options', 'ezyein-invoice-view', [ $this, 'page_view_invoice' ] );
    }

    public function enqueue_assets( $hook ) {
        $screens = [
            'toplevel_page_ezyein-dashboard',
            'ezy-e-invoice_page_ezyein-clients',
            'ezy-e-invoice_page_ezyein-products',
            'ezy-e-invoice_page_ezyein-invoices',
            'ezy-e-invoice_page_ezyein-create-invoice',
            'ezy-e-invoice_page_ezyein-settings',
            'admin_page_ezyein-invoice-view',
        ];
        if ( ! in_array( $hook, $screens ) ) return;

        wp_enqueue_style(  'ezyein-admin', EZYEIN_URL . 'admin/css/ezy-admin.css',  [], EZYEIN_VERSION );
        wp_enqueue_script( 'ezyein-admin', EZYEIN_URL . 'admin/js/ezy-admin.js', [ 'jquery', 'jquery-ui-autocomplete' ], EZYEIN_VERSION, true );
        wp_localize_script( 'ezyein-admin', 'ezyeinInvoice', [
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'ezyein_invoice_nonce' ),
            'currency'        => EZYEIN_Settings::get( 'currency_symbol', 'RM' ),
            'taxEnabled'      => (int) EZYEIN_Settings::get( 'tax_enabled', 1 ),
            'taxRate'         => (float) EZYEIN_Settings::get( 'tax_rate', 6 ),
            'taxLabel'        => EZYEIN_Settings::get( 'tax_label', 'SST' ),
            'scEnabled'       => (int) EZYEIN_Settings::get( 'service_charge_enabled', 0 ),
            'scRate'          => (float) EZYEIN_Settings::get( 'service_charge_rate', 10 ),
            'scLabel'         => EZYEIN_Settings::get( 'service_charge_label', 'Service Charge' ),
            'discountEnabled' => (int) EZYEIN_Settings::get( 'discount_enabled', 0 ),
            'invoicesUrl'     => admin_url( 'admin.php?page=ezyein-invoices' ),
        ] );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
    }

    public function page_dashboard()      { require EZYEIN_DIR . 'admin/views/page-dashboard.php'; }
    public function page_clients()        { require EZYEIN_DIR . 'admin/views/page-clients.php'; }
    public function page_products()       { require EZYEIN_DIR . 'admin/views/page-products.php'; }
    public function page_invoices()       { require EZYEIN_DIR . 'admin/views/page-invoices.php'; }
    public function page_create_invoice() { require EZYEIN_DIR . 'admin/views/page-create-invoice.php'; }
    public function page_settings()       { require EZYEIN_DIR . 'admin/views/page-settings.php'; }
    public function page_view_invoice()   { require EZYEIN_DIR . 'admin/views/page-view-invoice.php'; }

    public function enqueue_donate_banner( $hook ) {
        $screens = [
            'toplevel_page_ezyein-dashboard',
            'ezy-e-invoice_page_ezyein-clients',
            'ezy-e-invoice_page_ezyein-products',
            'ezy-e-invoice_page_ezyein-invoices',
            'ezy-e-invoice_page_ezyein-create-invoice',
            'ezy-e-invoice_page_ezyein-settings',
            'admin_page_ezyein-invoice-view',
        ];
        if ( ! in_array( $hook, $screens ) ) return;
        wp_enqueue_style( 'ezyein-donate-banner', EZYEIN_URL . 'admin/css/ezy-donate.css', [], EZYEIN_VERSION );
    }

    public function render_donate_banner( $hook ) {
        $screen = get_current_screen();
        $valid  = [
            'toplevel_page_ezyein-dashboard',
            'ezy-e-invoice_page_ezyein-clients',
            'ezy-e-invoice_page_ezyein-products',
            'ezy-e-invoice_page_ezyein-invoices',
            'ezy-e-invoice_page_ezyein-create-invoice',
            'ezy-e-invoice_page_ezyein-settings',
            'admin_page_ezyein-invoice-view',
        ];
        if ( ! $screen || ! in_array( $screen->id, $valid, true ) ) return;
        ?>
        <div class="ezy-donate-banner">
            <span class="ezy-donate-icon">&#9829;</span>
            <span class="ezy-donate-text">
                <?php esc_html_e( 'If ezy E Invoice helps your business and makes your life easier, please consider supporting its development.', 'ezy-e-invoice' ); ?>
            </span>
            <a class="ezy-donate-btn"
               href="https://www.paypal.com/donate?business=abssaiful%40gmail.com&currency_code=USD&item_name=ezy+E+Invoice+Plugin"
               target="_blank" rel="noopener noreferrer">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#003087" style="vertical-align:middle;margin-right:4px"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.93 4.778-4.005 7.201-9.138 7.201h-2.19a.563.563 0 0 0-.556.479l-1.187 7.527h-.506l-.24 1.516a.56.56 0 0 0 .554.647h3.882c.46 0 .85-.334.922-.788.06-.26.76-4.852.816-5.09a.932.932 0 0 1 .923-.788h.58c3.76 0 6.705-1.528 7.565-5.946.36-1.847.174-3.388-.777-4.471z"/></svg>
                <?php esc_html_e( 'Buy me a coffee ☕', 'ezy-e-invoice' ); ?>
            </a>
        </div>
        <?php
    }


    /**
     * Always regenerate and serve fresh PDF as a forced download.
     */
    public function handle_pdf_download() {
        if ( empty( $_GET['ezyein_action'] ) || 'download_pdf' !== $_GET['ezyein_action'] ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'ezy-e-invoice' ) );
        }
        $nonce = isset( $_GET['_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'ezyein_download_pdf' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'ezy-e-invoice' ) );
        }
        $id      = absint( $_GET['id'] ?? 0 );
        $invoice = EZYEIN_DB::get_invoice( $id );
        if ( ! $invoice ) {
            wp_die( esc_html__( 'Invoice not found.', 'ezy-e-invoice' ) );
        }

        // Always regenerate the PDF fresh on every download to ensure the latest
        // layout and data are used (avoids serving stale cached files).
        $pdf_path = EZYEIN_PDF::generate( $id );
        if ( $pdf_path ) {
            EZYEIN_DB::update_invoice_pdf( $id, $pdf_path );
        }

        if ( ! $pdf_path || ! file_exists( $pdf_path ) ) {
            wp_die( esc_html__( 'PDF could not be generated. Please check your server error log.', 'ezy-e-invoice' ) );
        }

        $ext      = strtolower( pathinfo( $pdf_path, PATHINFO_EXTENSION ) );
        $mime     = ( 'pdf' === $ext ) ? 'application/pdf' : 'text/html';
        $filename = 'invoice-' . sanitize_file_name( $invoice->invoice_number ) . '.' . $ext;

        // Output file with forced-download headers
        header( 'Content-Type: '        . $mime );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: '      . filesize( $pdf_path ) );
        header( 'Cache-Control: private, no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file output
        global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}
			$file_content = $wp_filesystem->get_contents( $pdf_path );
			if ( false !== $file_content ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $file_content;
			}
        exit;
    }

}
