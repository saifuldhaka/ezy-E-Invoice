<?php
/**
 * Plugin Name:       ezy E Invoice
 * Plugin URI:        https://github.com/saifuldhaka/ezy-E-Invoice
 * Description:       A modern and easy e-invoice plugin – create, send and manage professional invoices with PDF attachments. Supports WooCommerce product sync, client management, configurable tax / service charges and branded emails.
 * Version:           2.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            eHut.tech
 * Author URI:        https://ehut.tech/
 * Contributors:      abssaiful
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ezy-e-invoice
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EZYEIN_VERSION', '2.2.0' );
define( 'EZYEIN_DIR',     plugin_dir_path( __FILE__ ) );
define( 'EZYEIN_URL',     plugin_dir_url( __FILE__ ) );

require_once EZYEIN_DIR . 'includes/class-ezy-db.php';
require_once EZYEIN_DIR . 'includes/class-ezy-settings.php';
require_once EZYEIN_DIR . 'includes/class-ezy-clients.php';
require_once EZYEIN_DIR . 'includes/class-ezy-products.php';
require_once EZYEIN_DIR . 'includes/class-ezy-invoices.php';
require_once EZYEIN_DIR . 'includes/class-ezy-pdf.php';
require_once EZYEIN_DIR . 'includes/class-ezy-email.php';
require_once EZYEIN_DIR . 'admin/class-ezy-admin.php';

register_activation_hook( __FILE__, array( 'EZYEIN_DB', 'create_tables' ) );

function ezyein_plugin_init() {
    new EZYEIN_Admin();
    EZYEIN_Clients::init();
    EZYEIN_Products::init();
    EZYEIN_Invoices::init();
}
add_action( 'plugins_loaded', 'ezyein_plugin_init' );
