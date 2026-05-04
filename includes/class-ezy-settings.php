<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class EZYEIN_Settings {

    const GROUP = 'ezyein_settings';

    public static function get( $key, $default = '' ) {
        return get_option( 'ezyein_' . $key, $default );
    }

    public static function sections() {
        return [
            'company' => [
                'title'  => 'Company / Sender Information',
                'icon'   => 'dashicons-building',
                'fields' => [
                    'company_name'  => [ 'label' => 'Company Name',            'type' => 'text',     'required' => true ],
                    'company_logo'  => [ 'label' => 'Logo URL',                'type' => 'url',      'desc' => 'Full URL to your company logo image (PNG/JPG, recommended 200×80px)' ],
                    'address_line1' => [ 'label' => 'Address Line 1',          'type' => 'text' ],
                    'address_line2' => [ 'label' => 'Address Line 2',          'type' => 'text' ],
                    'city'          => [ 'label' => 'City',                    'type' => 'text' ],
                    'state'         => [ 'label' => 'State / Province',        'type' => 'text' ],
                    'country'       => [ 'label' => 'Country',                 'type' => 'text' ],
                    'postal_code'   => [ 'label' => 'Postal Code',             'type' => 'text' ],
                    'phone'         => [ 'label' => 'Phone',                   'type' => 'text' ],
                    'email'         => [ 'label' => 'Email',                   'type' => 'email' ],
                    'website'       => [ 'label' => 'Website',                 'type' => 'url' ],
                    'tax_number'    => [ 'label' => 'Tax / GST / SST Reg No.', 'type' => 'text' ],
                    'reg_number'    => [ 'label' => 'Company Reg No.',         'type' => 'text' ],
                ],
            ],
            'invoice' => [
                'title'  => 'Invoice Configuration',
                'icon'   => 'dashicons-media-document',
                'fields' => [
                    'prefix'         => [ 'label' => 'Invoice Prefix',          'type' => 'text',   'default' => 'INV-',  'desc' => 'e.g. INV-, EZY-, 2024-' ],
                    'next_number'    => [ 'label' => 'Next Invoice Number',      'type' => 'number', 'default' => '1' ],
                    'number_padding' => [ 'label' => 'Number Padding (digits)',  'type' => 'number', 'default' => '4',     'desc' => 'e.g. 4 → INV-0001' ],
                    'payment_terms'  => [ 'label' => 'Default Payment Terms (days)', 'type' => 'number', 'default' => '30' ],
                    'currency_symbol'=> [ 'label' => 'Currency Symbol',          'type' => 'text',   'default' => 'RM' ],
                    'currency_code'  => [ 'label' => 'Currency Code',            'type' => 'text',   'default' => 'MYR' ],
                    'date_format'    => [ 'label' => 'Date Format',              'type' => 'text',   'default' => 'd/m/Y', 'desc' => 'PHP date format, e.g. d/m/Y or Y-m-d' ],
                    'default_notes'  => [ 'label' => 'Default Invoice Notes',    'type' => 'textarea', 'desc' => 'Appears at bottom of every invoice (payment instructions, terms, etc.)' ],
                    'bank_details'   => [ 'label' => 'Bank Account Details',     'type' => 'textarea', 'desc' => 'Bank name, account number, etc.' ],
                ],
            ],
            'tax' => [
                'title'  => 'Tax & Service Charge',
                'icon'   => 'dashicons-calculator',
                'fields' => [
                    'tax_enabled'            => [ 'label' => 'Enable Tax',                  'type' => 'checkbox', 'default' => '1' ],
                    'tax_label'              => [ 'label' => 'Tax Label',                   'type' => 'text',     'default' => 'SST',             'desc' => 'e.g. GST, VAT, SST' ],
                    'tax_rate'               => [ 'label' => 'Tax Rate (%)',                 'type' => 'number',   'default' => '6',               'step' => '0.01' ],
                    'service_charge_enabled' => [ 'label' => 'Enable Service Charge',       'type' => 'checkbox' ],
                    'service_charge_label'   => [ 'label' => 'Service Charge Label',        'type' => 'text',     'default' => 'Service Charge' ],
                    'service_charge_rate'    => [ 'label' => 'Service Charge Rate (%)',     'type' => 'number',   'default' => '10',              'step' => '0.01' ],
                    'discount_enabled'       => [ 'label' => 'Allow Discount per Invoice',  'type' => 'checkbox' ],
                ],
            ],
            'email' => [
                'title'  => 'Email Settings',
                'icon'   => 'dashicons-email-alt',
                'fields' => [
                    'email_from_name'  => [ 'label' => 'From Name',      'type' => 'text',     'desc' => 'Sender name shown to recipient' ],
                    'email_from_email' => [ 'label' => 'From Email',     'type' => 'email',    'desc' => 'Leave blank to use WordPress default' ],
                    'email_subject'    => [ 'label' => 'Email Subject',  'type' => 'text',     'default' => 'Your Invoice {invoice_number} from {company_name}',
                                            'desc' => 'Placeholders: {invoice_number}, {company_name}, {client_name}, {total}' ],
                    'email_body'       => [ 'label' => 'Email Body',     'type' => 'textarea', 'default' => "Dear {client_name},\n\nPlease find attached your invoice {invoice_number} for {currency}{total}.\n\nPayment is due by {due_date}.\n\n{bank_details}\n\nThank you for your business!\n\nBest regards,\n{company_name}",
                                            'desc' => 'Placeholders: {client_name}, {invoice_number}, {total}, {currency}, {due_date}, {company_name}, {bank_details}' ],
                    'email_cc'         => [ 'label' => 'CC Email',       'type' => 'email',    'desc' => 'Optional CC recipient' ],
                    'email_bcc'        => [ 'label' => 'BCC Email',      'type' => 'email',    'desc' => 'Optional BCC recipient' ],
                ],
            ],
        ];
    }

    public static function register_hooks() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings() {
        foreach ( self::sections() as $section_id => $section ) {
            foreach ( $section['fields'] as $key => $field ) {
                $cb = 'sanitize_text_field';
                if ( in_array( $field['type'], [ 'email', 'url' ] ) ) $cb = 'sanitize_' . $field['type'];
                if ( $field['type'] === 'textarea' ) $cb = 'sanitize_textarea_field';
                if ( $field['type'] === 'number' )   $cb = 'floatval';
                if ( $field['type'] === 'checkbox' ) $cb = 'absint';
                register_setting( self::GROUP, 'ezyein_' . $key, [ 'sanitize_callback' => $cb ] );
            }
        }
    }

    public static function format_placeholders( $text, $invoice, $items = [] ) {
        $currency = self::get( 'currency_symbol', 'RM' );
        $fmt      = self::get( 'date_format', 'd/m/Y' );
        $pairs    = [
            '{invoice_number}' => $invoice->invoice_number ?? '',
            '{company_name}'   => self::get( 'company_name' ),
            '{client_name}'    => trim( ( $invoice->contact_name ?? '' ) . ' ' . ( $invoice->company_name ?? '' ) ),
            '{total}'          => number_format( (float) ( $invoice->total ?? 0 ), 2 ),
            '{currency}'       => $currency,
            '{due_date}'       => ! empty( $invoice->due_date ) ? gmdate( $fmt, strtotime( $invoice->due_date ) ) : '',
            '{bank_details}'   => self::get( 'bank_details' ),
        ];
        return str_replace( array_keys( $pairs ), array_values( $pairs ), $text );
    }
}
