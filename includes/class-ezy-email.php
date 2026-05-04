<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Ezy_Email {

    public static function send( $invoice, $attachment_path = '' ) {
        if ( empty( $invoice->client_email ) ) return false;

        $subject_tpl = Ezy_Settings::get( 'email_subject', 'Your Invoice {invoice_number} from {company_name}' );
        $body_tpl    = Ezy_Settings::get( 'email_body' );
        if ( empty( $body_tpl ) ) {
            $body_tpl = "Dear {client_name},\n\nPlease find attached your invoice {invoice_number}.\n\nAmount Due: {currency}{total}\nDue Date: {due_date}\n\n{bank_details}\n\nThank you for your business!\n\n{company_name}";
        }

        $subject = Ezy_Settings::format_placeholders( $subject_tpl, $invoice );
        $body    = Ezy_Settings::format_placeholders( $body_tpl,    $invoice );

        // Headers
        $from_name  = Ezy_Settings::get( 'email_from_name' )  ?: get_bloginfo( 'name' );
        $from_email = Ezy_Settings::get( 'email_from_email' ) ?: get_option( 'admin_email' );
        $headers    = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];
        $cc  = Ezy_Settings::get( 'email_cc' );
        $bcc = Ezy_Settings::get( 'email_bcc' );
        if ( $cc )  $headers[] = 'Cc: '  . $cc;
        if ( $bcc ) $headers[] = 'Bcc: ' . $bcc;

        $attachments = [];
        if ( $attachment_path && file_exists( $attachment_path ) ) $attachments[] = $attachment_path;

        // Also send HTML email version
        add_filter( 'wp_mail_content_type', [ __CLASS__, 'set_html_content_type' ] );
        $html_body = self::build_html_body( $invoice, $body );
        $headers   = array_map( fn( $h ) => str_replace( 'text/plain', 'text/html', $h ), $headers );

        $ok = wp_mail( $invoice->client_email, $subject, $html_body, $headers, $attachments );
        remove_filter( 'wp_mail_content_type', [ __CLASS__, 'set_html_content_type' ] );
        return $ok;
    }

    public static function set_html_content_type() { return 'text/html'; }

    private static function build_html_body( $invoice, $plain_body ) {
        $plain_body_html = nl2br( esc_html( $plain_body ) );
        $company         = esc_html( Ezy_Settings::get( 'company_name' ) );
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
  .wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
  .header { background: #1f497d; color: #fff; padding: 20px; border-radius: 6px 6px 0 0; }
  .body { background: #f9f9f9; padding: 25px; border: 1px solid #e0e0e0; }
  .footer { text-align: center; font-size: 12px; color: #999; padding: 15px; }
  .btn { display: inline-block; background: #1f497d; color: #fff !important; padding: 12px 24px;
         border-radius: 4px; text-decoration: none; margin-top: 15px; }
</style></head><body>
<div class='wrapper'>
  <div class='header'><h2 style='margin:0'>{$company}</h2></div>
  <div class='body'><p>{$plain_body_html}</p></div>
  <div class='footer'>This email was sent by ezy E Invoice &bull; {$company}</div>
</div></body></html>";
    }
}
