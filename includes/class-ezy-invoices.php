<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Ezy_Invoices {

    public static function init() {
        add_action( 'wp_ajax_ezyein_create_invoice',  [ __CLASS__, 'ajax_create'  ] );
        add_action( 'wp_ajax_ezyein_delete_invoice',  [ __CLASS__, 'ajax_delete'  ] );
        add_action( 'wp_ajax_ezyein_resend_invoice',  [ __CLASS__, 'ajax_resend'  ] );
        add_action( 'wp_ajax_ezyein_mark_paid',       [ __CLASS__, 'ajax_mark_paid' ] );
        add_action( 'wp_ajax_ezyein_get_invoice_number', [ __CLASS__, 'ajax_get_number' ] );
    }

    public static function ajax_get_number() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        wp_send_json_success( [ 'number' => Ezy_DB::generate_invoice_number() ] );
    }

    public static function ajax_create() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );

        // Validate required fields
        if ( empty( $_POST['client_id'] ) ) wp_send_json_error( 'Please select a client.' );
        if ( empty( $_POST['items'] ) )     wp_send_json_error( 'Please add at least one item.' );

        $items_raw = wp_unslash( $_POST['items'] );
        $items     = json_decode( $items_raw, true );
        if ( ! is_array( $items ) || empty( $items ) ) wp_send_json_error( 'Invalid items data.' );

        // Sanitize each item's fields after decoding
        $items = array_map( function( $item ) {
            return [
                'product_id'       => sanitize_text_field( $item['product_id'] ?? '' ),
                'item_name'        => sanitize_text_field( $item['item_name'] ?? 'Item' ),
                'item_description' => sanitize_textarea_field( $item['item_description'] ?? '' ),
                'unit_price'       => floatval( $item['unit_price'] ?? 0 ),
                'quantity'         => floatval( $item['quantity'] ?? 1 ),
            ];
        }, $items );

        // Gather settings
        $tax_rate    = (float) Ezy_Settings::get( 'tax_rate',    6 );
        $sc_rate     = (float) Ezy_Settings::get( 'service_charge_rate', 10 );
        $tax_enabled = Ezy_Settings::get( 'tax_enabled',            '1' );
        $sc_enabled  = Ezy_Settings::get( 'service_charge_enabled', '' );

        // Override from form if present
        $tax_rate    = isset( $_POST['tax_rate'] )              ? (float) $_POST['tax_rate']              : $tax_rate;
        $sc_rate     = isset( $_POST['service_charge_rate'] )   ? (float) $_POST['service_charge_rate']   : $sc_rate;
        $tax_enabled = isset( $_POST['tax_enabled'] )           ? (int)   $_POST['tax_enabled']           : $tax_enabled;
        $sc_enabled  = isset( $_POST['service_charge_enabled'] )? (int)   $_POST['service_charge_enabled']: $sc_enabled;

        // Calculate totals
        $subtotal = 0;
        foreach ( $items as $item ) { $subtotal += floatval( $item['unit_price'] ) * floatval( $item['quantity'] ); }
        $tax_amount = $tax_enabled  ? round( $subtotal * $tax_rate / 100, 2 ) : 0;
        $sc_amount  = $sc_enabled   ? round( $subtotal * $sc_rate  / 100, 2 ) : 0;
        $discount   = round( floatval( $_POST['discount_amount'] ?? 0 ), 2 );
        $total      = round( $subtotal + $tax_amount + $sc_amount - $discount, 2 );

        $payment_terms = (int) Ezy_Settings::get( 'payment_terms', 30 );
        $issue_date    = gmdate( 'Y-m-d' );
        $due_date      = gmdate( 'Y-m-d', strtotime( "+{$payment_terms} days" ) );

        if ( ! empty( $_POST['issue_date'] ) ) $issue_date = sanitize_text_field( wp_unslash( $_POST['issue_date'] ) );
        if ( ! empty( $_POST['due_date'] ) )   $due_date   = sanitize_text_field( wp_unslash( $_POST['due_date'] ) );

        $invoice_data = [
            'invoice_number'        => sanitize_text_field( wp_unslash( $_POST['invoice_number'] ?? Ezy_DB::generate_invoice_number() ) ),
            'client_id'             => absint( $_POST['client_id'] ),
            'issue_date'            => $issue_date,
            'due_date'              => $due_date,
            'status'                => 'sent',
            'subtotal'              => round( $subtotal, 2 ),
            'tax_rate'              => $tax_enabled  ? $tax_rate : 0,
            'tax_amount'            => $tax_amount,
            'service_charge_rate'   => $sc_enabled   ? $sc_rate  : 0,
            'service_charge_amount' => $sc_amount,
            'discount_amount'       => $discount,
            'total'                 => $total,
            'notes'                 => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
        ];

        $invoice_id = Ezy_DB::save_invoice( $invoice_data, $items );
        if ( ! $invoice_id ) wp_send_json_error( 'Failed to save invoice. Please try again.' );

        // Generate PDF
        $pdf_path = Ezy_PDF::generate( $invoice_id );
        if ( $pdf_path ) Ezy_DB::update_invoice_pdf( $invoice_id, $pdf_path );

        // Send email
        $invoice  = Ezy_DB::get_invoice( $invoice_id );
        $email_ok = Ezy_Email::send( $invoice, $pdf_path );
        if ( $email_ok ) Ezy_DB::mark_invoice_sent( $invoice_id );

        $admin_url = admin_url( 'admin.php?page=ezy-invoice-view&id=' . $invoice_id );
        wp_send_json_success( [
            'invoice_id'     => $invoice_id,
            'invoice_number' => $invoice_data['invoice_number'],
            'email_sent'     => $email_ok,
            'view_url'       => $admin_url,
            'message'        => $email_ok
                ? 'Invoice created and emailed to client successfully!'
                : 'Invoice created but email could not be sent. Please send it manually.',
        ] );
    }

    public static function ajax_delete() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $id      = absint( $_POST['id'] ?? 0 );
        $invoice = Ezy_DB::get_invoice( $id );
        if ( ! $invoice ) wp_send_json_error( 'Invoice not found.' );
        // Delete PDF file if exists
        if ( ! empty( $invoice->pdf_path ) && file_exists( $invoice->pdf_path ) ) wp_delete_file( $invoice->pdf_path );
        Ezy_DB::delete_invoice( $id );
        wp_send_json_success( [ 'message' => 'Invoice deleted.' ] );
    }

    public static function ajax_resend() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $id      = absint( $_POST['id'] ?? 0 );
        $invoice = Ezy_DB::get_invoice( $id );
        if ( ! $invoice ) wp_send_json_error( 'Invoice not found.' );
        $pdf_path = $invoice->pdf_path;
        if ( empty( $pdf_path ) || ! file_exists( $pdf_path ) ) {
            $pdf_path = Ezy_PDF::generate( $id );
            if ( $pdf_path ) Ezy_DB::update_invoice_pdf( $id, $pdf_path );
        }
        $ok = Ezy_Email::send( $invoice, $pdf_path );
        if ( $ok ) Ezy_DB::mark_invoice_sent( $id );
        if ( $ok ) wp_send_json_success( [ 'message' => 'Invoice resent successfully!' ] );
        else       wp_send_json_error( 'Failed to send email. Check your email settings.' );
    }

    public static function ajax_mark_paid() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $id = absint( $_POST['id'] ?? 0 );
        Ezy_DB::update_invoice_status( $id, 'paid' );
        wp_send_json_success( [ 'message' => 'Invoice marked as paid.' ] );
    }
}
