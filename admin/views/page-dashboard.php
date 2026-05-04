<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial included within class methods; variables are not in global scope.

$stats    = EZYEIN_DB::get_stats();
$currency = EZYEIN_Settings::get( 'currency_symbol', 'RM' );
?>
<div class="wrap ezy-wrap">
  <div class="ezy-header">
    <h1><span class="dashicons dashicons-media-document"></span> ezy E Invoice — Dashboard</h1>
  </div>

  <div class="ezy-stats-grid">
    <div class="ezy-stat-card ezy-stat-blue">
      <span class="ezy-stat-icon dashicons dashicons-media-document"></span>
      <div class="ezy-stat-num"><?php echo (int) $stats['total_invoices']; ?></div>
      <div class="ezy-stat-lbl">Total Invoices</div>
    </div>
    <div class="ezy-stat-card ezy-stat-green">
      <span class="ezy-stat-icon dashicons dashicons-money-alt"></span>
      <div class="ezy-stat-num"><?php echo esc_html( $currency ); ?> <?php echo number_format( (float) $stats['total_revenue'], 2 ); ?></div>
      <div class="ezy-stat-lbl">Total Revenue</div>
    </div>
    <div class="ezy-stat-card ezy-stat-teal">
      <span class="ezy-stat-icon dashicons dashicons-admin-users"></span>
      <div class="ezy-stat-num"><?php echo (int) $stats['total_clients']; ?></div>
      <div class="ezy-stat-lbl">Active Clients</div>
    </div>
    <div class="ezy-stat-card ezy-stat-purple">
      <span class="ezy-stat-icon dashicons dashicons-cart"></span>
      <div class="ezy-stat-num"><?php echo (int) $stats['total_products']; ?></div>
      <div class="ezy-stat-lbl">Products</div>
    </div>
  </div>

  <div class="ezy-row">
    <div class="ezy-col-2">
      <div class="ezy-card">
        <h2>Invoice Status</h2>
        <table class="ezy-status-table">
          <tr><td><span class="ezy-badge ezy-badge-blue">Draft</span></td>    <td><?php echo (int) $stats['draft'];   ?></td></tr>
          <tr><td><span class="ezy-badge ezy-badge-green">Sent</span></td>    <td><?php echo (int) $stats['sent'];    ?></td></tr>
          <tr><td><span class="ezy-badge ezy-badge-gold">Paid</span></td>     <td><?php echo (int) $stats['paid'];    ?></td></tr>
          <tr><td><span class="ezy-badge ezy-badge-red">Overdue</span></td>   <td><?php echo (int) $stats['overdue']; ?></td></tr>
        </table>
      </div>
    </div>
    <div class="ezy-col-2">
      <div class="ezy-card">
        <h2>Quick Actions</h2>
        <a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-create-invoice') ); ?>" class="button button-primary ezy-btn-block">
          <span class="dashicons dashicons-plus-alt2"></span> Create New Invoice
        </a>
        <a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-clients') ); ?>" class="button ezy-btn-block">
          <span class="dashicons dashicons-admin-users"></span> Manage Clients
        </a>
        <a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-products') ); ?>" class="button ezy-btn-block">
          <span class="dashicons dashicons-cart"></span> Manage Products
        </a>
        <a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-settings') ); ?>" class="button ezy-btn-block">
          <span class="dashicons dashicons-admin-settings"></span> Settings
        </a>
      </div>
    </div>
  </div>

  <?php if ( ! empty( $stats['recent'] ) ) : ?>
  <div class="ezy-card">
    <h2>Recent Invoices</h2>
    <table class="wp-list-table widefat fixed striped">
      <thead><tr>
        <th>Invoice #</th><th>Client</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th>
      </tr></thead>
      <tbody>
      <?php foreach ( $stats['recent'] as $inv ) : ?>
        <tr>
          <td><strong><?php echo esc_html( $inv->invoice_number ); ?></strong></td>
          <td><?php echo esc_html( $inv->contact_name . ( $inv->company_name ? ' / ' . $inv->company_name : '' ) ); ?></td>
          <td><?php echo esc_html( $currency . ' ' . number_format( (float) $inv->total, 2 ) ); ?></td>
          <td><span class="ezy-badge ezy-badge-<?php echo esc_attr( $inv->status ); ?>"><?php echo esc_html( ucfirst( $inv->status ) ); ?></span></td>
          <td><?php echo esc_html( gmdate( EZYEIN_Settings::get('date_format','d/m/Y'), strtotime( $inv->created_at ) ) ); ?></td>
          <td><a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-invoice-view&id=' . $inv->id) ); ?>" class="button button-small">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
