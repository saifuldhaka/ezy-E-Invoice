<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ezy_invoice_items" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ezy_invoices" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ezy_products" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ezy_clients" );
$options = [
    'ezy_invoice_db_version','ezy_invoice_next_number','ezy_invoice_prefix',
    'ezy_invoice_number_padding','ezy_invoice_payment_terms',
    'ezy_invoice_company_name','ezy_invoice_company_logo','ezy_invoice_address_line1',
    'ezy_invoice_address_line2','ezy_invoice_city','ezy_invoice_state',
    'ezy_invoice_country','ezy_invoice_postal_code','ezy_invoice_phone',
    'ezy_invoice_email','ezy_invoice_website','ezy_invoice_tax_number',
    'ezy_invoice_reg_number','ezy_invoice_currency_symbol','ezy_invoice_currency_code',
    'ezy_invoice_date_format','ezy_invoice_default_notes','ezy_invoice_bank_details',
    'ezy_invoice_tax_enabled','ezy_invoice_tax_label','ezy_invoice_tax_rate',
    'ezy_invoice_service_charge_enabled','ezy_invoice_service_charge_label',
    'ezy_invoice_service_charge_rate','ezy_invoice_email_from_name',
    'ezy_invoice_email_from_email','ezy_invoice_email_subject','ezy_invoice_email_cc',
    'ezy_invoice_email_bcc','ezy_invoice_email_body',
];
foreach ( $options as $opt ) { delete_option( $opt ); }
