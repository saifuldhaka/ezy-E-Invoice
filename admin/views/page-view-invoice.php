<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial included inside class method; variables are not in global scope.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

$id      = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display page; user capability check provides security.
$invoice = EZYEIN_DB::get_invoice( $id );
if ( ! $invoice ) wp_die( 'Invoice not found.' );
$items    = EZYEIN_DB::get_invoice_items( $id );
$currency = EZYEIN_Settings::get( 'currency_symbol', 'RM' );
$fmt      = EZYEIN_Settings::get( 'date_format', 'd/m/Y' );
$tax_lbl  = EZYEIN_Settings::get( 'tax_label', 'SST' );
$sc_lbl   = EZYEIN_Settings::get( 'service_charge_label', 'Service Charge' );
$company  = EZYEIN_Settings::get( 'company_name' );
$is_overdue = $invoice->status === 'sent' && strtotime( $invoice->due_date ) < time();
$display_status = $is_overdue ? 'overdue' : $invoice->status;
?>
<div class="wrap ezy-wrap">
  <div class="ezy-header">
    <h1>
      <span class="dashicons dashicons-media-document"></span>
      Invoice <?php echo esc_html( $invoice->invoice_number ); ?>
      <span class="ezy-badge ezy-badge-<?php echo esc_attr($display_status); ?>"><?php echo esc_html(ucfirst($display_status)); ?></span>
    </h1>
    <div class="ezy-header-actions">
      <a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-invoices') ); ?>" class="button">&larr; All Invoices</a>
      <button class="button ezy-resend-invoice" data-id="<?php echo (int) $id; ?>">
        <span class="dashicons dashicons-email-alt"></span> Resend Email
      </button>
      <?php if ( $invoice->status !== 'paid' ) : ?>
      <button class="button ezy-mark-paid" data-id="<?php echo (int) $id; ?>">
        <span class="dashicons dashicons-yes"></span> Mark as Paid
      </button>
      <?php endif; ?>
      <?php
        $dl_url = add_query_arg( [
            'ezyein_action' => 'download_pdf',
            'id'            => $id,
            '_nonce'        => wp_create_nonce( 'ezyein_download_pdf' ),
        ], admin_url( 'admin.php' ) );
      ?>
      <a href="<?php echo esc_url( $dl_url ); ?>" class="button button-primary">
        <span class="dashicons dashicons-download"></span> Download PDF
      </a>
    </div>
  </div>

  <div id="ezy-action-msg" class="notice" style="display:none;"></div>

  <!-- Invoice Preview -->
  <div class="ezy-invoice-preview">

    <!-- Header -->
    <div class="ezy-inv-header">
      <div class="ezy-inv-from">
        <?php $logo = EZYEIN_Settings::get('company_logo'); if ($logo) : ?>
        <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($company); ?>" class="ezy-inv-logo" />
        <?php endif; ?>
        <strong class="ezy-inv-company"><?php echo esc_html( $company ); ?></strong>
        <?php foreach ( ['address_line1','address_line2','city','state','country','postal_code','phone','email','tax_number'] as $f ) : ?>
          <?php $val = EZYEIN_Settings::get($f); if ($val) echo '<div>' . esc_html($val) . '</div>'; ?>
        <?php endforeach; ?>
      </div>
      <div class="ezy-inv-title-block">
        <div class="ezy-inv-title">INVOICE</div>
        <table class="ezy-inv-meta">
          <tr><th>Invoice No.</th><td><?php echo esc_html($invoice->invoice_number); ?></td></tr>
          <tr><th>Date</th><td><?php echo esc_html(gmdate($fmt, strtotime($invoice->issue_date))); ?></td></tr>
          <tr><th>Due Date</th><td><?php echo esc_html(gmdate($fmt, strtotime($invoice->due_date))); ?></td></tr>
          <tr><th>Status</th><td><span class="ezy-badge ezy-badge-<?php echo esc_attr($display_status); ?>"><?php echo esc_html(ucfirst($display_status)); ?></span></td></tr>
        </table>
      </div>
    </div>

    <!-- Bill To -->
    <div class="ezy-inv-bill-to">
      <div class="ezy-inv-section-label">BILL TO</div>
      <strong><?php echo esc_html($invoice->contact_name); ?></strong>
      <?php if ($invoice->company_name) echo '<div>' . esc_html($invoice->company_name) . '</div>'; ?>
      <?php foreach ( ['address_line1','address_line2'] as $f ) : if (!empty($invoice->$f)) echo '<div>' . esc_html($invoice->$f) . '</div>'; endforeach; ?>
      <?php
        $cityline = trim( $invoice->city . ' ' . $invoice->postal_code );
        if ($cityline) echo '<div>' . esc_html($cityline) . '</div>';
        $stctry = trim( $invoice->state_province . ' ' . $invoice->country );
        if ($stctry) echo '<div>' . esc_html($stctry) . '</div>';
        if ($invoice->client_email) echo '<div>' . esc_html($invoice->client_email) . '</div>';
        if ($invoice->client_phone) echo '<div>Tel: ' . esc_html($invoice->client_phone) . '</div>';
        if ($invoice->client_tax_number) echo '<div>Tax No: ' . esc_html($invoice->client_tax_number) . '</div>';
      ?>
    </div>

    <!-- Items -->
    <table class="ezy-inv-items">
      <thead>
        <tr>
          <th class="num">#</th>
          <th>Description</th>
          <th class="right">Unit Price</th>
          <th class="right">Qty</th>
          <th class="right">Total</th>
        </tr>
      </thead>
      <tbody>
      <?php $i = 1; foreach ($items as $item) : ?>
      <tr>
        <td class="num"><?php echo (int) $i++; ?></td>
        <td>
          <strong><?php echo esc_html($item->item_name); ?></strong>
          <?php if ($item->item_description) echo '<div class="ezy-item-desc">' . esc_html($item->item_description) . '</div>'; ?>
        </td>
        <td class="right"><?php echo esc_html($currency . ' ' . number_format((float)$item->unit_price, 2)); ?></td>
        <td class="right"><?php echo esc_html(number_format((float)$item->quantity, 2)); ?></td>
        <td class="right"><?php echo esc_html($currency . ' ' . number_format((float)$item->line_total, 2)); ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Totals -->
    <div class="ezy-inv-totals">
      <table>
        <tr><td>Subtotal</td><td><?php echo esc_html($currency . ' ' . number_format((float)$invoice->subtotal, 2)); ?></td></tr>
        <?php if ((float)$invoice->tax_amount > 0) : ?>
        <tr><td><?php echo esc_html($tax_lbl . ' (' . $invoice->tax_rate . '%)'); ?></td><td><?php echo esc_html($currency . ' ' . number_format((float)$invoice->tax_amount, 2)); ?></td></tr>
        <?php endif; ?>
        <?php if ((float)$invoice->service_charge_amount > 0) : ?>
        <tr><td><?php echo esc_html($sc_lbl . ' (' . $invoice->service_charge_rate . '%)'); ?></td><td><?php echo esc_html($currency . ' ' . number_format((float)$invoice->service_charge_amount, 2)); ?></td></tr>
        <?php endif; ?>
        <?php if ((float)$invoice->discount_amount > 0) : ?>
        <tr><td>Discount</td><td>-<?php echo esc_html($currency . ' ' . number_format((float)$invoice->discount_amount, 2)); ?></td></tr>
        <?php endif; ?>
        <tr class="ezy-inv-grand-total">
          <td><strong>TOTAL</strong></td>
          <td><strong><?php echo esc_html($currency . ' ' . number_format((float)$invoice->total, 2)); ?></strong></td>
        </tr>
      </table>
    </div>

    <!-- Notes -->
    <?php if ($invoice->notes || EZYEIN_Settings::get('bank_details')) : ?>
    <div class="ezy-inv-notes">
      <?php if ($invoice->notes) : ?>
        <div class="ezy-inv-section-label">NOTES / TERMS</div>
        <p><?php echo nl2br(esc_html($invoice->notes)); ?></p>
      <?php endif; ?>
      <?php $bank = EZYEIN_Settings::get('bank_details'); if ($bank) : ?>
        <div class="ezy-inv-section-label">PAYMENT DETAILS</div>
        <p><?php echo nl2br(esc_html($bank)); ?></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="ezy-inv-footer">Thank you for your business! — Generated by ezy E Invoice</div>
  </div>

  <!-- Email log -->
  <div class="ezy-card" style="margin-top:20px;">
    <h3>Email History</h3>
    <?php if ($invoice->email_sent_at) : ?>
    <p><span class="dashicons dashicons-email-alt" style="color:#2c9f2c"></span> Last sent: <?php echo esc_html(gmdate($fmt . ' H:i', strtotime($invoice->email_sent_at))); ?></p>
    <?php else : ?>
    <p>No email sent yet.</p>
    <?php endif; ?>
  </div>
</div>
