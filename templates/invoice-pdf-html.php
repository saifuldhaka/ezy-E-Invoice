<?php
/**
 * Standalone HTML invoice template used for offline PDF generation and
 * email attachment fallback. This file is captured via ob_start() and saved
 * to disk — it is NOT rendered inside WordPress. Inline <style> is required
 * for a self-contained HTML document and does not use wp_enqueue_style().
 *
 * @package ezy-e-invoice
 */
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial included within class methods; variables are not in global scope.

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice <?php echo esc_html($invoice->invoice_number ?? ''); ?></title>
<style>
  body { font-family: Arial, sans-serif; font-size: 13px; color: #333; margin: 40px; }
  .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
  .company-name { font-size: 20px; font-weight: bold; color: #1f497d; }
  .inv-title { font-size: 36px; font-weight: bold; color: #1f497d; text-align:right; }
  .inv-number { font-size: 14px; color: #666; text-align:right; }
  .bill-section { margin: 20px 0; padding: 15px; background: #f5f8fc; border-radius: 4px; }
  .section-label { font-weight: bold; color: #1f497d; text-transform: uppercase; font-size: 11px; margin-bottom: 5px; }
  table { width: 100%; border-collapse: collapse; margin: 20px 0; }
  thead th { background: #1f497d; color: #fff; padding: 8px 12px; text-align: left; }
  tbody tr:nth-child(even) { background: #f2f6fc; }
  tbody td { padding: 8px 12px; border-bottom: 1px solid #eee; }
  .right { text-align: right; }
  .totals-table { width: 300px; margin-left: auto; }
  .totals-table td { padding: 5px 10px; }
  .grand-total { background: #1f497d; color: #fff; font-size: 15px; font-weight: bold; }
  .grand-total td { padding: 8px 10px; }
  .footer { text-align: center; color: #999; font-size: 11px; margin-top: 40px; border-top: 1px solid #eee; padding-top: 15px; }
  @media print { body { margin: 0; } }
</style>
</head>
<body>
<div class="header">
  <div>
    <?php $logo = $settings['company_logo'] ?? ''; if ($logo) : ?>
    <img src="<?php echo esc_url($logo); ?>" style="max-height:60px; margin-bottom:10px; display:block;" />
    <?php endif; ?>
    <div class="company-name"><?php echo esc_html($settings['company_name'] ?? ''); ?></div>
    <div><?php echo esc_html($settings['address_line1'] ?? ''); ?></div>
    <div><?php echo esc_html($settings['phone'] ?? ''); ?> &bull; <?php echo esc_html($settings['email'] ?? ''); ?></div>
  </div>
  <div>
    <div class="inv-title">INVOICE</div>
    <div class="inv-number"><?php echo esc_html($invoice->invoice_number ?? ''); ?></div>
    <div>Date: <?php echo !empty($invoice->issue_date) ? esc_html( gmdate($fmt, strtotime($invoice->issue_date)) ) : ''; ?></div>
    <div>Due: <?php echo !empty($invoice->due_date)   ? esc_html( gmdate($fmt, strtotime($invoice->due_date)) )   : ''; ?></div>
  </div>
</div>

<div class="bill-section">
  <div class="section-label">Bill To</div>
  <strong><?php echo esc_html($invoice->contact_name ?? ''); ?></strong>
  <?php if (!empty($invoice->company_name)) echo '<div>' . esc_html($invoice->company_name) . '</div>'; ?>
  <?php if (!empty($invoice->client_email)) echo '<div>' . esc_html($invoice->client_email) . '</div>'; ?>
</div>

<table>
  <thead><tr><th>#</th><th>Description</th><th class="right">Unit Price</th><th class="right">Qty</th><th class="right">Total</th></tr></thead>
  <tbody>
  <?php $n=1; foreach ($items as $item): ?>
  <tr>
    <td><?php echo (int) $n++; ?></td>
    <td><strong><?php echo esc_html($item->item_name); ?></strong><?php if($item->item_description) echo '<div style="font-size:11px;color:#777">'.esc_html($item->item_description).'</div>'; ?></td>
    <td class="right"><?php echo esc_html($cur.' '.number_format((float)$item->unit_price,2)); ?></td>
    <td class="right"><?php echo esc_html(number_format((float)$item->quantity,2)); ?></td>
    <td class="right"><?php echo esc_html($cur.' '.number_format((float)$item->line_total,2)); ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<table class="totals-table">
  <tr><td>Subtotal</td><td class="right"><?php echo esc_html($cur.' '.number_format((float)$invoice->subtotal,2)); ?></td></tr>
  <?php if ((float)($invoice->tax_amount??0)>0): ?>
  <tr><td><?php echo esc_html($settings['tax_label']??'Tax'); ?> (<?php echo esc_html($invoice->tax_rate); ?>%)</td><td class="right"><?php echo esc_html($cur.' '.number_format((float)$invoice->tax_amount,2)); ?></td></tr>
  <?php endif; ?>
  <?php if ((float)($invoice->service_charge_amount??0)>0): ?>
  <tr><td><?php echo esc_html($settings['sc_label']??'Service Charge'); ?> (<?php echo esc_html($invoice->service_charge_rate); ?>%)</td><td class="right"><?php echo esc_html($cur.' '.number_format((float)$invoice->service_charge_amount,2)); ?></td></tr>
  <?php endif; ?>
  <?php if ((float)($invoice->discount_amount??0)>0): ?>
  <tr><td>Discount</td><td class="right">-<?php echo esc_html($cur.' '.number_format((float)$invoice->discount_amount,2)); ?></td></tr>
  <?php endif; ?>
  <tr class="grand-total"><td>TOTAL</td><td class="right"><?php echo esc_html($cur.' '.number_format((float)$invoice->total,2)); ?></td></tr>
</table>

<?php if (!empty($invoice->notes)): ?>
<div style="margin-top:20px;"><div class="section-label">Notes / Terms</div><p><?php echo nl2br(esc_html($invoice->notes)); ?></p></div>
<?php endif; ?>
<?php if (!empty($settings['bank_details'])): ?>
<div><div class="section-label">Payment Details</div><p><?php echo nl2br(esc_html($settings['bank_details'])); ?></p></div>
<?php endif; ?>

<div class="footer">Thank you for your business! — Generated by ezy E Invoice</div>
</body></html>
