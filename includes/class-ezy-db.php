<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class EZYEIN_DB {

    public static function create_tables() {
        global $wpdb;
        $c = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( "CREATE TABLE {$wpdb->prefix}ezy_clients (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            company_name varchar(200) NOT NULL DEFAULT '',
            contact_name varchar(200) NOT NULL DEFAULT '',
            email varchar(200) NOT NULL DEFAULT '',
            phone varchar(50) NOT NULL DEFAULT '',
            address_line1 varchar(255) NOT NULL DEFAULT '',
            address_line2 varchar(255) NOT NULL DEFAULT '',
            city varchar(100) NOT NULL DEFAULT '',
            state_province varchar(100) NOT NULL DEFAULT '',
            country varchar(100) NOT NULL DEFAULT '',
            postal_code varchar(20) NOT NULL DEFAULT '',
            tax_number varchar(100) NOT NULL DEFAULT '',
            notes text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY email (email(50))
        ) $c;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}ezy_products (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text NOT NULL,
            sku varchar(100) NOT NULL DEFAULT '',
            price decimal(15,2) NOT NULL DEFAULT 0.00,
            unit varchar(50) NOT NULL DEFAULT 'unit',
            wc_product_id bigint(20) unsigned DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id)
        ) $c;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}ezy_invoices (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            invoice_number varchar(50) NOT NULL DEFAULT '',
            client_id bigint(20) unsigned NOT NULL DEFAULT 0,
            issue_date date NOT NULL DEFAULT '0000-00-00',
            due_date date NOT NULL DEFAULT '0000-00-00',
            status varchar(20) NOT NULL DEFAULT 'draft',
            subtotal decimal(15,2) NOT NULL DEFAULT 0.00,
            tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            tax_amount decimal(15,2) NOT NULL DEFAULT 0.00,
            service_charge_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            service_charge_amount decimal(15,2) NOT NULL DEFAULT 0.00,
            discount_amount decimal(15,2) NOT NULL DEFAULT 0.00,
            total decimal(15,2) NOT NULL DEFAULT 0.00,
            notes text NOT NULL,
            pdf_path varchar(500) NOT NULL DEFAULT '',
            email_sent_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY client_id (client_id)
        ) $c;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}ezy_invoice_items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) unsigned NOT NULL DEFAULT 0,
            product_id bigint(20) unsigned DEFAULT NULL,
            item_name varchar(255) NOT NULL DEFAULT '',
            item_description text NOT NULL,
            unit_price decimal(15,2) NOT NULL DEFAULT 0.00,
            quantity decimal(10,2) NOT NULL DEFAULT 1.00,
            line_total decimal(15,2) NOT NULL DEFAULT 0.00,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY invoice_id (invoice_id)
        ) $c;" );

        update_option( 'ezyein_db_version', EZYEIN_VERSION );
        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/ezy-invoices';
        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
            file_put_contents( $pdf_dir . '/.htaccess', "Options -Indexes\n" );
        }
    }

    public static function deactivate() {}

    /* ── CLIENTS ─────────────────────────────────────────── */

    public static function get_clients( $args = [] ) {
        global $wpdb;
        $t = $wpdb->prefix . 'ezy_clients';
        $search = $args['search'] ?? '';
        $limit  = isset( $args['limit'] )  ? (int) $args['limit']  : 20;
        $offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
        $where  = "WHERE status = 'active'"; $vals = [];
        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= " AND (contact_name LIKE %s OR company_name LIKE %s OR email LIKE %s)";
            $vals  = [ $like, $like, $like ];
        }
        $vals[] = $limit; $vals[] = $offset;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t $where ORDER BY contact_name ASC LIMIT %d OFFSET %d", $vals ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    }

    public static function count_clients( $search = '' ) {
        global $wpdb;
        $t = $wpdb->prefix . 'ezy_clients';
        $where = "WHERE status = 'active'"; $vals = [];
        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= " AND (contact_name LIKE %s OR company_name LIKE %s OR email LIKE %s)";
            $vals  = [ $like, $like, $like ];
        }
        $sql = "SELECT COUNT(*) FROM $t $where"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $vals ? $wpdb->get_var( $wpdb->prepare( $sql, $vals ) ) : $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    }

    public static function get_client( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ezy_clients WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    public static function save_client( $data ) {
        global $wpdb;
        $fields = [
            'company_name'   => sanitize_text_field( $data['company_name']   ?? '' ),
            'contact_name'   => sanitize_text_field( $data['contact_name']   ?? '' ),
            'email'          => sanitize_email(      $data['email']          ?? '' ),
            'phone'          => sanitize_text_field( $data['phone']          ?? '' ),
            'address_line1'  => sanitize_text_field( $data['address_line1']  ?? '' ),
            'address_line2'  => sanitize_text_field( $data['address_line2']  ?? '' ),
            'city'           => sanitize_text_field( $data['city']           ?? '' ),
            'state_province' => sanitize_text_field( $data['state_province'] ?? '' ),
            'country'        => sanitize_text_field( $data['country']        ?? '' ),
            'postal_code'    => sanitize_text_field( $data['postal_code']    ?? '' ),
            'tax_number'     => sanitize_text_field( $data['tax_number']     ?? '' ),
            'notes'          => sanitize_textarea_field( $data['notes']      ?? '' ),
            'status'         => 'active',
        ];
        $id = absint( $data['id'] ?? 0 );
        if ( $id ) { $wpdb->update( $wpdb->prefix . 'ezy_clients', $fields, [ 'id' => $id ] ); return $id; } // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $fields['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'ezy_clients', $fields ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->insert_id;
    }

    public static function delete_client( $id ) {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'ezy_clients', [ 'status' => 'deleted' ], [ 'id' => absint( $id ) ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /* ── PRODUCTS ────────────────────────────────────────── */

    public static function get_products( $args = [] ) {
        global $wpdb;
        $t = $wpdb->prefix . 'ezy_products';
        $search = $args['search'] ?? '';
        $limit  = isset( $args['limit'] )  ? (int) $args['limit']  : 20;
        $offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
        $where  = "WHERE status = 'active'"; $vals = [];
        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= " AND (name LIKE %s OR sku LIKE %s)";
            $vals  = [ $like, $like ];
        }
        $vals[] = $limit; $vals[] = $offset;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t $where ORDER BY name ASC LIMIT %d OFFSET %d", $vals ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    }

    public static function count_products( $search = '' ) {
        global $wpdb;
        $t = $wpdb->prefix . 'ezy_products';
        $where = "WHERE status = 'active'"; $vals = [];
        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= " AND (name LIKE %s OR sku LIKE %s)";
            $vals  = [ $like, $like ];
        }
        $sql = "SELECT COUNT(*) FROM $t $where"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $vals ? $wpdb->get_var( $wpdb->prepare( $sql, $vals ) ) : $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    }

    public static function get_product( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ezy_products WHERE id = %d", absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    public static function save_product( $data ) {
        global $wpdb;
        $fields = [
            'name'          => sanitize_text_field(     $data['name']          ?? '' ),
            'description'   => sanitize_textarea_field( $data['description']   ?? '' ),
            'sku'           => sanitize_text_field(     $data['sku']           ?? '' ),
            'price'         => floatval(                $data['price']         ?? 0  ),
            'unit'          => sanitize_text_field(     $data['unit']          ?? 'unit' ),
            'wc_product_id' => ! empty( $data['wc_product_id'] ) ? absint( $data['wc_product_id'] ) : null,
            'status'        => 'active',
        ];
        $id = absint( $data['id'] ?? 0 );
        if ( $id ) { $wpdb->update( $wpdb->prefix . 'ezy_products', $fields, [ 'id' => $id ] ); return $id; } // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $fields['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'ezy_products', $fields ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->insert_id;
    }

    public static function delete_product( $id ) {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'ezy_products', [ 'status' => 'deleted' ], [ 'id' => absint( $id ) ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /* ── INVOICES ────────────────────────────────────────── */

    public static function get_invoices( $args = [] ) {
        global $wpdb;
        $limit  = isset( $args['limit'] )  ? (int) $args['limit']  : 20;
        $offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
        $status = $args['status'] ?? ''; $search = $args['search'] ?? '';
        $where  = 'WHERE 1=1'; $vals = [];
        if ( $status ) { $where .= ' AND i.status = %s'; $vals[] = $status; }
        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= ' AND (i.invoice_number LIKE %s OR c.contact_name LIKE %s OR c.company_name LIKE %s)';
            $vals[] = $like; $vals[] = $like; $vals[] = $like;
        }
        $vals[] = $limit; $vals[] = $offset;
        $sql = "SELECT i.*, c.contact_name, c.company_name, c.email AS client_email
                FROM {$wpdb->prefix}ezy_invoices i
                LEFT JOIN {$wpdb->prefix}ezy_clients c ON i.client_id = c.id
                $where ORDER BY i.created_at DESC LIMIT %d OFFSET %d";
        return $wpdb->get_results( $wpdb->prepare( $sql, $vals ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    }

    public static function count_invoices( $status = '', $search = '' ) {
        global $wpdb;
        $where = 'WHERE 1=1'; $vals = [];
        if ( $status ) { $where .= ' AND i.status = %s'; $vals[] = $status; }
        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= ' AND (i.invoice_number LIKE %s OR c.contact_name LIKE %s OR c.company_name LIKE %s)';
            $vals[] = $like; $vals[] = $like; $vals[] = $like;
        }
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ezy_invoices i LEFT JOIN {$wpdb->prefix}ezy_clients c ON i.client_id = c.id $where";
        return $vals ? $wpdb->get_var( $wpdb->prepare( $sql, $vals ) ) : $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    }

    public static function get_invoice( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT i.*, c.contact_name, c.company_name, c.email AS client_email,
                    c.phone AS client_phone, c.address_line1, c.address_line2,
                    c.city, c.state_province, c.country, c.postal_code,
                    c.tax_number AS client_tax_number
             FROM {$wpdb->prefix}ezy_invoices i
             LEFT JOIN {$wpdb->prefix}ezy_clients c ON i.client_id = c.id
             WHERE i.id = %d", absint( $id ) ) );
    }

    public static function get_invoice_items( $invoice_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT * FROM {$wpdb->prefix}ezy_invoice_items WHERE invoice_id = %d ORDER BY sort_order ASC",
            absint( $invoice_id ) ) );
    }

    public static function generate_invoice_number() {
        global $wpdb;
        $prefix  = get_option( 'ezyein_prefix', 'INV-' );
        $padding = (int) get_option( 'ezyein_number_padding', 4 );
        $last    = $wpdb->get_var( "SELECT invoice_number FROM {$wpdb->prefix}ezy_invoices ORDER BY id DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
        $next    = $last ? ( (int) preg_replace( '/[^0-9]/', '', $last ) + 1 ) : (int) get_option( 'ezyein_next_number', 1 );
        update_option( 'ezyein_next_number', $next + 1 );
        return $prefix . str_pad( $next, $padding, '0', STR_PAD_LEFT );
    }

    public static function save_invoice( $data, $items ) {
        global $wpdb;
        $row = [
            'invoice_number'        => sanitize_text_field( $data['invoice_number'] ),
            'client_id'             => absint( $data['client_id'] ),
            'issue_date'            => sanitize_text_field( $data['issue_date'] ),
            'due_date'              => sanitize_text_field( $data['due_date'] ),
            'status'                => sanitize_text_field( $data['status'] ?? 'sent' ),
            'subtotal'              => round( floatval( $data['subtotal'] ), 2 ),
            'tax_rate'              => round( floatval( $data['tax_rate'] ), 2 ),
            'tax_amount'            => round( floatval( $data['tax_amount'] ), 2 ),
            'service_charge_rate'   => round( floatval( $data['service_charge_rate'] ), 2 ),
            'service_charge_amount' => round( floatval( $data['service_charge_amount'] ), 2 ),
            'discount_amount'       => round( floatval( $data['discount_amount'] ?? 0 ), 2 ),
            'total'                 => round( floatval( $data['total'] ), 2 ),
            'notes'                 => sanitize_textarea_field( $data['notes'] ?? '' ),
            'created_at'            => current_time( 'mysql' ),
        ];
        $wpdb->insert( $wpdb->prefix . 'ezy_invoices', $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $invoice_id = $wpdb->insert_id;
        if ( ! $invoice_id ) return false;
        foreach ( $items as $i => $item ) {
            $qty = floatval( $item['quantity'] ); $prc = floatval( $item['unit_price'] );
            $wpdb->insert( $wpdb->prefix . 'ezy_invoice_items', [ // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                'invoice_id'       => $invoice_id,
                'product_id'       => ! empty( $item['product_id'] ) ? absint( $item['product_id'] ) : null,
                'item_name'        => sanitize_text_field(     $item['item_name']        ?? '' ),
                'item_description' => sanitize_textarea_field( $item['item_description'] ?? '' ),
                'unit_price' => $prc, 'quantity' => $qty,
                'line_total' => round( $qty * $prc, 2 ),
                'sort_order' => $i,
            ] );
        }
        return $invoice_id;
    }

    public static function update_invoice_pdf( $id, $path ) {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'ezy_invoices', [ 'pdf_path' => $path ], [ 'id' => absint( $id ) ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    public static function mark_invoice_sent( $id ) {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'ezy_invoices', // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            [ 'email_sent_at' => current_time( 'mysql' ), 'status' => 'sent' ],
            [ 'id' => absint( $id ) ] );
    }

    public static function update_invoice_status( $id, $status ) {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'ezy_invoices', [ 'status' => sanitize_text_field( $status ) ], [ 'id' => absint( $id ) ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    public static function delete_invoice( $id ) {
        global $wpdb;
        $id = absint( $id );
        $wpdb->delete( $wpdb->prefix . 'ezy_invoice_items', [ 'invoice_id' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $wpdb->prefix . 'ezy_invoices',      [ 'id' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    public static function get_stats() {
        global $wpdb; $p = $wpdb->prefix;
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
        return [
            'total_invoices' => $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ezy_invoices" ),
            'total_clients'  => $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ezy_clients WHERE status='active'" ),
            'total_products' => $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ezy_products WHERE status='active'" ),
            'total_revenue'  => $wpdb->get_var( "SELECT COALESCE(SUM(total),0) FROM {$p}ezy_invoices WHERE status IN ('sent','paid')" ),
            'sent'   => $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ezy_invoices WHERE status='sent'" ),
            'paid'   => $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ezy_invoices WHERE status='paid'" ),
            'draft'  => $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ezy_invoices WHERE status='draft'" ),
            'overdue'=> $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ezy_invoices WHERE status='sent' AND due_date < CURDATE()" ),
            'recent' => $wpdb->get_results( "SELECT i.*, c.contact_name, c.company_name FROM {$p}ezy_invoices i LEFT JOIN {$p}ezy_clients c ON i.client_id=c.id ORDER BY i.created_at DESC LIMIT 5" ),
        ];
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
    }
}
