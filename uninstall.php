<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ezy_invoice_items" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ezy_invoices" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ezy_products" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ezy_clients" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
$ezyein_options = [
    'ezyein_db_version','ezyein_next_number','ezyein_prefix',
    'ezyein_number_padding','ezyein_payment_terms',
    'ezyein_company_name','ezyein_company_logo','ezyein_address_line1',
    'ezyein_address_line2','ezyein_city','ezyein_state',
    'ezyein_country','ezyein_postal_code','ezyein_phone',
    'ezyein_email','ezyein_website','ezyein_tax_number',
    'ezyein_reg_number','ezyein_currency_symbol','ezyein_currency_code',
    'ezyein_date_format','ezyein_default_notes','ezyein_bank_details',
    'ezyein_tax_enabled','ezyein_tax_label','ezyein_tax_rate',
    'ezyein_service_charge_enabled','ezyein_service_charge_label',
    'ezyein_service_charge_rate','ezyein_email_from_name',
    'ezyein_email_from_email','ezyein_email_subject','ezyein_email_cc',
    'ezyein_email_bcc','ezyein_email_body',
];
foreach ( $ezyein_options as $ezyein_opt ) { delete_option( $ezyein_opt ); }
