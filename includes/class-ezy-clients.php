<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class EZYEIN_Clients {

    public static function init() {
        add_action( 'wp_ajax_ezyein_search_clients', [ __CLASS__, 'ajax_search' ] );
        add_action( 'wp_ajax_ezyein_save_client',    [ __CLASS__, 'ajax_save'   ] );
        add_action( 'wp_ajax_ezyein_delete_client',  [ __CLASS__, 'ajax_delete' ] );
        add_action( 'wp_ajax_ezyein_get_client',     [ __CLASS__, 'ajax_get'    ] );
    }

    public static function ajax_search() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $search  = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
        $clients = EZYEIN_DB::get_clients( [ 'search' => $search, 'limit' => 15 ] );
        $results = [];
        foreach ( $clients as $c ) {
            $label     = esc_html( $c->contact_name );
            if ( $c->company_name ) $label .= ' — ' . esc_html( $c->company_name );
            $results[] = [ 'id' => $c->id, 'label' => $label, 'email' => $c->email ];
        }
        wp_send_json_success( $results );
    }

    public static function ajax_get() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $id     = absint( $_GET['id'] ?? 0 );
        $client = EZYEIN_DB::get_client( $id );
        if ( ! $client ) wp_send_json_error( 'Not found', 404 );
        wp_send_json_success( $client );
    }

    public static function ajax_save() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $id = EZYEIN_DB::save_client( $_POST );
        if ( ! $id ) wp_send_json_error( 'Could not save client' );
        wp_send_json_success( [ 'id' => $id, 'message' => 'Client saved successfully.' ] );
    }

    public static function ajax_delete() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        EZYEIN_DB::delete_client( absint( $_POST['id'] ?? 0 ) );
        wp_send_json_success( [ 'message' => 'Client deleted.' ] );
    }
}
