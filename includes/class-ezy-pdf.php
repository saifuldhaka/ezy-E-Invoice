<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Ezy_PDF {

    public static function generate( $invoice_id ) {
        $invoice = Ezy_DB::get_invoice( $invoice_id );
        $items   = Ezy_DB::get_invoice_items( $invoice_id );
        if ( ! $invoice ) return false;

        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/ezy-invoices';
        if ( ! file_exists( $pdf_dir ) ) wp_mkdir_p( $pdf_dir );

        $filename = 'invoice-' . sanitize_file_name( $invoice->invoice_number ) . '.pdf';
        $filepath = $pdf_dir . '/' . $filename;

        // Try FPDF
        $fpdf_path = EZY_INVOICE_DIR . 'lib/fpdf/fpdf.php';
        if ( file_exists( $fpdf_path ) ) {
            if ( ! class_exists( 'FPDF' ) ) {
                require_once $fpdf_path;
            }
            return self::generate_with_fpdf( $invoice, $items, $filepath );
        }

        // Fallback: save as HTML with .pdf extension note
        return self::generate_html_fallback( $invoice, $items, $filepath );
    }

    private static function generate_with_fpdf( $invoice, $items, $filepath ) {
        $settings = [
            'company_name'    => Ezy_Settings::get( 'company_name' ),
            'company_logo'    => Ezy_Settings::get( 'company_logo' ),
            'address_line1'   => Ezy_Settings::get( 'address_line1' ),
            'address_line2'   => Ezy_Settings::get( 'address_line2' ),
            'city'            => Ezy_Settings::get( 'city' ),
            'state'           => Ezy_Settings::get( 'state' ),
            'country'         => Ezy_Settings::get( 'country' ),
            'postal_code'     => Ezy_Settings::get( 'postal_code' ),
            'phone'           => Ezy_Settings::get( 'phone' ),
            'email'           => Ezy_Settings::get( 'email' ),
            'tax_number'      => Ezy_Settings::get( 'tax_number' ),
            'reg_number'      => Ezy_Settings::get( 'reg_number' ),
            'currency'        => Ezy_Settings::get( 'currency_symbol', 'RM' ),
            'date_format'     => Ezy_Settings::get( 'date_format', 'd/m/Y' ),
            'bank_details'    => Ezy_Settings::get( 'bank_details' ),
            'tax_label'       => Ezy_Settings::get( 'tax_label', 'SST' ),
            'sc_label'        => Ezy_Settings::get( 'service_charge_label', 'Service Charge' ),
        ];

        try {
            $pdf = new FPDF( 'P', 'mm', 'A4' );
            $pdf->AddPage();
            $pdf->SetMargins( 15, 15, 15 );
            $pdf->SetAutoPageBreak( true, 20 );
            $pageW = 180; // 210 - 30 margins

            // ── HEADER ───────────────────────────────────────────────────────
            // Logo
            if ( $settings['company_logo'] ) {
                $logo_path = self::url_to_path( $settings['company_logo'] );
                if ( $logo_path && file_exists( $logo_path ) ) {
                    $pdf->Image( $logo_path, 15, 15, 40 );
                    $pdf->Ln( 12 );
                }
            }

            // Company info (left)
            $pdf->SetFont( 'Arial', 'B', 14 );
            $pdf->SetTextColor( 31, 73, 125 );
            $pdf->Cell( $pageW / 2, 7, $settings['company_name'], 0, 0, 'L' );

            // INVOICE title (right)
            $pdf->SetFont( 'Arial', 'B', 22 );
            $pdf->SetTextColor( 31, 73, 125 );
            $pdf->Cell( $pageW / 2, 7, 'INVOICE', 0, 1, 'R' );

            $pdf->SetFont( 'Arial', '', 9 );
            $pdf->SetTextColor( 80, 80, 80 );
            $addr_parts = array_filter( [
                $settings['address_line1'], $settings['address_line2'],
                trim( $settings['city'] . ' ' . $settings['postal_code'] ),
                trim( $settings['state'] . ' ' . $settings['country'] ),
            ] );
            foreach ( $addr_parts as $part ) {
                $pdf->Cell( $pageW / 2, 5, $part, 0, 0, 'L' );
                $pdf->Ln( 5 );
            }
            if ( $settings['phone'] ) { $pdf->Cell( $pageW / 2, 5, 'Tel: ' . $settings['phone'], 0, 1 ); }
            if ( $settings['email'] ) { $pdf->Cell( $pageW / 2, 5, 'Email: ' . $settings['email'], 0, 1 ); }
            if ( $settings['tax_number'] ) { $pdf->Cell( $pageW / 2, 5, 'Tax No: ' . $settings['tax_number'], 0, 1 ); }
            if ( $settings['reg_number'] ) { $pdf->Cell( $pageW / 2, 5, 'Reg No: ' . $settings['reg_number'], 0, 1 ); }

            $pdf->Ln( 3 );

            // Divider
            $pdf->SetDrawColor( 31, 73, 125 );
            $pdf->SetLineWidth( 0.5 );
            $pdf->Line( 15, $pdf->GetY(), 195, $pdf->GetY() );
            $pdf->Ln( 4 );

            // ── INVOICE META ─────────────────────────────────────────────────
            $fmt      = $settings['date_format'];
            $col1     = $pageW * 0.55;
            $col2     = $pageW * 0.45;
            $curY     = $pdf->GetY();

            // Bill To
            $pdf->SetFont( 'Arial', 'B', 10 );
            $pdf->SetTextColor( 31, 73, 125 );
            $pdf->Cell( $col1, 6, 'BILL TO:', 0, 1 );
            $pdf->SetFont( 'Arial', 'B', 11 );
            $pdf->SetTextColor( 30, 30, 30 );
            $client_name = trim( $invoice->contact_name . ( $invoice->company_name ? ' / ' . $invoice->company_name : '' ) );
            $pdf->Cell( $col1, 6, $client_name, 0, 1 );
            $pdf->SetFont( 'Arial', '', 9 );
            $pdf->SetTextColor( 80, 80, 80 );
            $c_addr = array_filter( [
                $invoice->address_line1, $invoice->address_line2,
                trim( $invoice->city . ' ' . $invoice->postal_code ),
                trim( $invoice->state_province . ' ' . $invoice->country ),
            ] );
            foreach ( $c_addr as $part ) { $pdf->Cell( $col1, 5, $part, 0, 1 ); }
            if ( $invoice->client_email ) $pdf->Cell( $col1, 5, $invoice->client_email, 0, 1 );
            if ( $invoice->client_phone ) $pdf->Cell( $col1, 5, 'Tel: ' . $invoice->client_phone, 0, 1 );
            if ( $invoice->client_tax_number ) $pdf->Cell( $col1, 5, 'Tax No: ' . $invoice->client_tax_number, 0, 1 );

            // Invoice meta (right side)
            $pdf->SetXY( 15 + $col1, $curY );
            $meta = [
                'Invoice No.'  => $invoice->invoice_number,
                'Date'         => gmdate( $fmt, strtotime( $invoice->issue_date ) ),
                'Due Date'     => gmdate( $fmt, strtotime( $invoice->due_date ) ),
                'Status'       => strtoupper( $invoice->status ),
                'Currency'     => $settings['currency'],
            ];
            foreach ( $meta as $label => $value ) {
                $pdf->SetX( 15 + $col1 );
                $pdf->SetFont( 'Arial', 'B', 9 );
                $pdf->SetTextColor( 80, 80, 80 );
                $pdf->Cell( 30, 6, $label . ':', 0 );
                $pdf->SetFont( 'Arial', '', 9 );
                $pdf->SetTextColor( 30, 30, 30 );
                $pdf->Cell( $col2 - 30, 6, $value, 0, 1 );
            }

            $pdf->Ln( 5 );

            // ── ITEMS TABLE ──────────────────────────────────────────────────
            $col_no   =  8;
            $col_desc = 80;
            $col_qty  = 16;
            $col_unit = 28;
            $col_tot  = 28;

            // Header
            $pdf->SetFillColor( 31, 73, 125 );
            $pdf->SetTextColor( 255, 255, 255 );
            $pdf->SetFont( 'Arial', 'B', 9 );
            $pdf->Cell( $col_no,   7, '#',          1, 0, 'C', true );
            $pdf->Cell( $col_desc, 7, 'DESCRIPTION',1, 0, 'L', true );
            $pdf->Cell( $col_qty,  7, 'QTY',        1, 0, 'C', true );
            $pdf->Cell( $col_unit, 7, 'UNIT PRICE', 1, 0, 'R', true );
            $pdf->Cell( $col_tot,  7, 'TOTAL',      1, 1, 'R', true );

            // Rows
            $pdf->SetFont( 'Arial', '', 9 );
            $pdf->SetTextColor( 30, 30, 30 );
            $row_num = 1;
            foreach ( $items as $item ) {
                $fill = ( $row_num % 2 === 0 );
                $pdf->SetFillColor( 242, 246, 252 );
                $pdf->Cell( $col_no,   6, $row_num, 1, 0, 'C', $fill );
                $desc = $item->item_name . ( $item->item_description ? "\n  " . $item->item_description : '' );
                $x    = $pdf->GetX(); $y = $pdf->GetY();
                $pdf->MultiCell( $col_desc, 6, $item->item_name, 1, 'L', $fill );
                $newY = $pdf->GetY();
                $pdf->SetXY( $x + $col_desc, $y );
                $rowH = $newY - $y;
                $pdf->Cell( $col_qty,  $rowH, number_format( (float) $item->quantity, 2 ), 1, 0, 'C', $fill );
                $pdf->Cell( $col_unit, $rowH, $settings['currency'] . ' ' . number_format( (float) $item->unit_price, 2 ), 1, 0, 'R', $fill );
                $pdf->Cell( $col_tot,  $rowH, $settings['currency'] . ' ' . number_format( (float) $item->line_total, 2 ), 1, 1, 'R', $fill );
                $row_num++;
            }

            // ── TOTALS ───────────────────────────────────────────────────────
            $pdf->Ln( 2 );
            $label_x = 15 + $col_no + $col_desc + $col_qty;
            $label_w = $col_unit;
            $val_w   = $col_tot;

            $totals = [
                'Subtotal' => $invoice->subtotal,
            ];
            if ( (float) $invoice->tax_amount > 0 ) {
                $totals[ $settings['tax_label'] . ' (' . $invoice->tax_rate . '%)' ] = $invoice->tax_amount;
            }
            if ( (float) $invoice->service_charge_amount > 0 ) {
                $totals[ $settings['sc_label'] . ' (' . $invoice->service_charge_rate . '%)' ] = $invoice->service_charge_amount;
            }
            if ( (float) $invoice->discount_amount > 0 ) {
                $totals['Discount'] = '-' . $invoice->discount_amount;
            }

            $pdf->SetFont( 'Arial', '', 9 );
            $pdf->SetTextColor( 60, 60, 60 );
            foreach ( $totals as $lbl => $val ) {
                $pdf->SetX( $label_x );
                $pdf->Cell( $label_w, 6, $lbl . ':', 0, 0, 'R' );
                $pdf->Cell( $val_w,   6, $settings['currency'] . ' ' . number_format( (float) $val, 2 ), 0, 1, 'R' );
            }

            // TOTAL
            $pdf->SetFillColor( 31, 73, 125 );
            $pdf->SetTextColor( 255, 255, 255 );
            $pdf->SetFont( 'Arial', 'B', 11 );
            $pdf->SetX( $label_x );
            $pdf->Cell( $label_w, 8, 'TOTAL', 'T', 0, 'R', true );
            $pdf->Cell( $val_w,   8, $settings['currency'] . ' ' . number_format( (float) $invoice->total, 2 ), 'T', 1, 'R', true );

            // ── NOTES & BANK DETAILS ─────────────────────────────────────────
            $pdf->Ln( 6 );
            if ( $invoice->notes || $settings['bank_details'] ) {
                $pdf->SetDrawColor( 200, 200, 200 );
                $pdf->SetLineWidth( 0.3 );
                $pdf->Line( 15, $pdf->GetY(), 195, $pdf->GetY() );
                $pdf->Ln( 3 );
            }
            if ( $invoice->notes ) {
                $pdf->SetFont( 'Arial', 'B', 9 );
                $pdf->SetTextColor( 31, 73, 125 );
                $pdf->Cell( $pageW, 5, 'NOTES / TERMS', 0, 1 );
                $pdf->SetFont( 'Arial', '', 9 );
                $pdf->SetTextColor( 60, 60, 60 );
                $pdf->MultiCell( $pageW, 5, $invoice->notes, 0, 'L' );
                $pdf->Ln( 3 );
            }
            if ( $settings['bank_details'] ) {
                $pdf->SetFont( 'Arial', 'B', 9 );
                $pdf->SetTextColor( 31, 73, 125 );
                $pdf->Cell( $pageW, 5, 'PAYMENT DETAILS', 0, 1 );
                $pdf->SetFont( 'Arial', '', 9 );
                $pdf->SetTextColor( 60, 60, 60 );
                $pdf->MultiCell( $pageW, 5, $settings['bank_details'], 0, 'L' );
            }

            // ── FOOTER ───────────────────────────────────────────────────────
            $pdf->SetY( -20 );
            $pdf->SetFont( 'Arial', 'I', 8 );
            $pdf->SetTextColor( 150, 150, 150 );
            $pdf->Cell( $pageW, 5, 'Thank you for your business! — Generated by ezy E Invoice', 0, 0, 'C' );

            $pdf->Output( 'F', $filepath );
            return $filepath;

        } catch ( Exception $e ) {
            return self::generate_html_fallback( Ezy_DB::get_invoice( $invoice->id ), Ezy_DB::get_invoice_items( $invoice->id ), $filepath );
        }
    }

    private static function url_to_path( $url ) {
        $upload_dir = wp_upload_dir();
        if ( strpos( $url, $upload_dir['baseurl'] ) !== false ) {
            return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
        }
        $site_url = site_url();
        if ( strpos( $url, $site_url ) !== false ) {
            return str_replace( $site_url, ABSPATH, $url );
        }
        return false;
    }

    private static function generate_html_fallback( $invoice, $items, $filepath ) {
        // Save as an HTML file that can be printed as PDF
        $html_path = str_replace( '.pdf', '.html', $filepath );
        $settings  = [
            'company_name' => Ezy_Settings::get( 'company_name' ),
            'currency'     => Ezy_Settings::get( 'currency_symbol', 'RM' ),
            'date_format'  => Ezy_Settings::get( 'date_format', 'd/m/Y' ),
            'tax_label'    => Ezy_Settings::get( 'tax_label', 'SST' ),
            'sc_label'     => Ezy_Settings::get( 'service_charge_label', 'Service Charge' ),
            'bank_details' => Ezy_Settings::get( 'bank_details' ),
            'address_line1'=> Ezy_Settings::get( 'address_line1' ),
            'phone'        => Ezy_Settings::get( 'phone' ),
            'email'        => Ezy_Settings::get( 'email' ),
        ];
        $fmt = $settings['date_format'];
        $cur = $settings['currency'];

        ob_start();
        include EZY_INVOICE_DIR . 'templates/invoice-pdf-html.php';
        $html = ob_get_clean();
        file_put_contents( $html_path, $html );
        return $html_path;
    }
}
