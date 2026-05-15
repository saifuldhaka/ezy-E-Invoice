<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class EZYEIN_Products {

    public static function init() {
        add_action( 'wp_ajax_ezyein_search_products',  [ __CLASS__, 'ajax_search'  ] );
        add_action( 'wp_ajax_ezyein_get_product',      [ __CLASS__, 'ajax_get'     ] );
        add_action( 'wp_ajax_ezyein_save_product',     [ __CLASS__, 'ajax_save'    ] );
        add_action( 'wp_ajax_ezyein_delete_product',   [ __CLASS__, 'ajax_delete'  ] );
        add_action( 'wp_ajax_ezyein_sync_wc_products', [ __CLASS__, 'ajax_sync_wc' ] );
    }

    public static function ajax_search() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $search   = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
        $products = EZYEIN_DB::get_products( [ 'search' => $search, 'limit' => 15 ] );
        $results  = [];
        foreach ( $products as $p ) {
            $label     = esc_html( $p->name );
            if ( $p->sku ) $label .= ' [' . esc_html( $p->sku ) . ']';
            $results[] = [ 'id' => $p->id, 'label' => $label, 'name' => $p->name,
                           'price' => (float) $p->price, 'unit' => $p->unit,
                           'description' => $p->description ];
        }
        wp_send_json_success( $results );
    }

    public static function ajax_get() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $id      = absint( $_GET['id'] ?? 0 );
        $product = EZYEIN_DB::get_product( $id );
        if ( ! $product ) wp_send_json_error( 'Not found', 404 );
        wp_send_json_success( $product );
    }

    public static function ajax_save() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        $data = [
            'id'          => absint( wp_unslash( $_POST['id'] ?? 0 ) ),
            'name'        => sanitize_text_field( wp_unslash( $_POST['name']        ?? '' ) ),
            'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'sku'         => sanitize_text_field( wp_unslash( $_POST['sku']         ?? '' ) ),
            'price'       => floatval( wp_unslash( $_POST['price'] ?? 0 ) ),
            'unit'        => sanitize_text_field( wp_unslash( $_POST['unit']        ?? 'unit' ) ),
        ];
        $id = EZYEIN_DB::save_product( $data );
        if ( ! $id ) wp_send_json_error( 'Could not save product' );
        wp_send_json_success( [ 'id' => $id, 'message' => 'Product saved successfully.' ] );
    }

    public static function ajax_delete() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        EZYEIN_DB::delete_product( absint( $_POST['id'] ?? 0 ) );
        wp_send_json_success( [ 'message' => 'Product deleted.' ] );
    }

    public static function ajax_sync_wc() {
        check_ajax_referer( 'ezyein_invoice_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
        if ( ! class_exists( 'WooCommerce' ) ) wp_send_json_error( 'WooCommerce is not active.' );

        $args = [ 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish' ];
        $wc_products = get_posts( $args );
        $synced = 0;
        foreach ( $wc_products as $wc_post ) {
            $wc_product = wc_get_product( $wc_post->ID );
            if ( ! $wc_product ) continue;
            global $wpdb;
            $exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                "SELECT id FROM {$wpdb->prefix}ezy_products WHERE wc_product_id = %d AND status = 'active'",
                $wc_post->ID ) );
            $data = [
                'name'          => $wc_product->get_name(),
                'description'   => wp_strip_all_tags( $wc_product->get_short_description() ?: $wc_product->get_name() ),
                'sku'           => $wc_product->get_sku(),
                'price'         => (float) $wc_product->get_price(),
                'unit'          => 'unit',
                'wc_product_id' => $wc_post->ID,
            ];
            if ( $exists ) { $data['id'] = $exists; }
            EZYEIN_DB::save_product( $data );
            $synced++;
        }
        wp_send_json_success( [ 'message' => "Synced $synced WooCommerce products." ] );
    }
}
