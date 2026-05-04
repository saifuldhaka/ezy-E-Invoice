<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Ezy_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menus'  ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets'  ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_donate_banner' ] );
        add_action( 'in_admin_header',       [ $this, 'render_donate_banner'  ] );
        Ezy_Settings::register_hooks();
    }

    public function register_menus() {
        add_menu_page(
            'ezy E Invoice', 'ezy E Invoice',
            'manage_options', 'ezy-invoice',
            [ $this, 'page_dashboard' ],
            'dashicons-media-document', 30
        );
        add_submenu_page( 'ezy-invoice', 'Dashboard',      'Dashboard',      'manage_options', 'ezy-invoice',         [ $this, 'page_dashboard'      ] );
        add_submenu_page( 'ezy-invoice', 'Clients',        'Clients',        'manage_options', 'ezy-clients',         [ $this, 'page_clients'        ] );
        add_submenu_page( 'ezy-invoice', 'Products',       'Products',       'manage_options', 'ezy-products',        [ $this, 'page_products'       ] );
        add_submenu_page( 'ezy-invoice', 'Invoices',       'All Invoices',   'manage_options', 'ezy-invoices',        [ $this, 'page_invoices'       ] );
        add_submenu_page( 'ezy-invoice', 'Create Invoice', 'Create Invoice', 'manage_options', 'ezy-create-invoice',  [ $this, 'page_create_invoice' ] );
        add_submenu_page( 'ezy-invoice', 'Settings',       'Settings',       'manage_options', 'ezy-settings',        [ $this, 'page_settings'       ] );
        // Hidden view page
        add_submenu_page( null, 'View Invoice', 'View Invoice', 'manage_options', 'ezy-invoice-view', [ $this, 'page_view_invoice' ] );
    }

    public function enqueue_assets( $hook ) {
        $screens = [
            'toplevel_page_ezy-invoice',
            'ezy-e-invoice_page_ezy-clients',
            'ezy-e-invoice_page_ezy-products',
            'ezy-e-invoice_page_ezy-invoices',
            'ezy-e-invoice_page_ezy-create-invoice',
            'ezy-e-invoice_page_ezy-settings',
            'admin_page_ezy-invoice-view',
        ];
        if ( ! in_array( $hook, $screens ) ) return;

        wp_enqueue_style(  'ezy-invoice-admin', EZY_INVOICE_URL . 'admin/css/ezy-admin.css',  [], EZY_INVOICE_VERSION );
        wp_enqueue_script( 'ezy-invoice-admin', EZY_INVOICE_URL . 'admin/js/ezy-admin.js', [ 'jquery', 'jquery-ui-autocomplete' ], EZY_INVOICE_VERSION, true );
        wp_localize_script( 'ezy-invoice-admin', 'ezyInvoice', [
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'ezyein_invoice_nonce' ),
            'currency'        => Ezy_Settings::get( 'currency_symbol', 'RM' ),
            'taxEnabled'      => (int) Ezy_Settings::get( 'tax_enabled', 1 ),
            'taxRate'         => (float) Ezy_Settings::get( 'tax_rate', 6 ),
            'taxLabel'        => Ezy_Settings::get( 'tax_label', 'SST' ),
            'scEnabled'       => (int) Ezy_Settings::get( 'service_charge_enabled', 0 ),
            'scRate'          => (float) Ezy_Settings::get( 'service_charge_rate', 10 ),
            'scLabel'         => Ezy_Settings::get( 'service_charge_label', 'Service Charge' ),
            'discountEnabled' => (int) Ezy_Settings::get( 'discount_enabled', 0 ),
            'invoicesUrl'     => admin_url( 'admin.php?page=ezy-invoices' ),
        ] );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
    }

    public function page_dashboard()      { require EZY_INVOICE_DIR . 'admin/views/page-dashboard.php'; }
    public function page_clients()        { require EZY_INVOICE_DIR . 'admin/views/page-clients.php'; }
    public function page_products()       { require EZY_INVOICE_DIR . 'admin/views/page-products.php'; }
    public function page_invoices()       { require EZY_INVOICE_DIR . 'admin/views/page-invoices.php'; }
    public function page_create_invoice() { require EZY_INVOICE_DIR . 'admin/views/page-create-invoice.php'; }
    public function page_settings()       { require EZY_INVOICE_DIR . 'admin/views/page-settings.php'; }
    public function page_view_invoice()   { require EZY_INVOICE_DIR . 'admin/views/page-view-invoice.php'; }

    public function enqueue_donate_banner( $hook ) {
        $screens = [
            'toplevel_page_ezy-invoice',
            'ezy-e-invoice_page_ezy-clients',
            'ezy-e-invoice_page_ezy-products',
            'ezy-e-invoice_page_ezy-invoices',
            'ezy-e-invoice_page_ezy-create-invoice',
            'ezy-e-invoice_page_ezy-settings',
            'admin_page_ezy-invoice-view',
        ];
        if ( ! in_array( $hook, $screens ) ) return;
        wp_enqueue_style( 'ezy-donate-banner', EZY_INVOICE_URL . 'admin/css/ezy-donate.css', [], EZY_INVOICE_VERSION );
    }

    public function render_donate_banner( $hook ) {
        $screen = get_current_screen();
        $valid  = [
            'toplevel_page_ezy-invoice',
            'ezy-e-invoice_page_ezy-clients',
            'ezy-e-invoice_page_ezy-products',
            'ezy-e-invoice_page_ezy-invoices',
            'ezy-e-invoice_page_ezy-create-invoice',
            'ezy-e-invoice_page_ezy-settings',
            'admin_page_ezy-invoice-view',
        ];
        if ( ! $screen || ! in_array( $screen->id, $valid, true ) ) return;
        ?>
        <div class="ezy-donate-banner">
            <span class="ezy-donate-icon">&#9829;</span>
            <span class="ezy-donate-text">
                <?php esc_html_e( 'If ezy E Invoice helps your business and makes your life easier, please consider supporting its development.', 'ezy-block-visibility' ); ?>
            </span>
            <a class="ezy-donate-btn"
               href="https://www.paypal.com/donate?business=abssaiful%40gmail.com&currency_code=USD&item_name=ezy+E+Invoice+Plugin"
               target="_blank" rel="noopener noreferrer">
                <img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif"
                     alt="<?php esc_attr_e( 'Donate via PayPal', 'ezy-block-visibility' ); ?>" />
                <?php esc_html_e( 'Buy me a coffee ☕', 'ezy-block-visibility' ); ?>
            </a>
        </div>
        <?php
    }

}
