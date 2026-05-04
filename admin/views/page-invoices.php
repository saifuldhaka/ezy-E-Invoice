<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial included inside class method; variables are not in global scope.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

$search   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$status   = sanitize_text_field( wp_unslash( $_GET['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$paged    = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$perpage  = 20;
$offset   = ( $paged - 1 ) * $perpage;
$total    = EZYEIN_DB::count_invoices( $status, $search );
$invoices = EZYEIN_DB::get_invoices( [ 'search' => $search, 'status' => $status, 'limit' => $perpage, 'offset' => $offset ] );
$pages    = ceil( $total / $perpage );
$currency = EZYEIN_Settings::get( 'currency_symbol', 'RM' );
$fmt      = EZYEIN_Settings::get( 'date_format', 'd/m/Y' );
$statuses = [ '' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue' ];
?>
<div class="wrap ezy-wrap">
  <div class="ezy-header">
    <h1><span class="dashicons dashicons-list-view"></span> All Invoices
      <a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-create-invoice') ); ?>" class="button button-primary">+ Create Invoice</a>
    </h1>
  </div>

  <!-- Filters -->
  <div class="ezy-filters">
    <?php foreach ( $statuses as $s => $label ) : ?>
    <a href="?page=ezyein-invoices<?php echo $s ? '&status=' . urlencode($s) : ''; ?><?php echo $search ? '&s=' . urlencode($search) : ''; ?>"
       class="ezy-filter-tab <?php echo $status === $s ? 'active' : ''; ?>">
      <?php echo esc_html( $label ); ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="ezy-card">
    <form method="get" action="" class="ezy-search-form">
      <input type="hidden" name="page" value="ezyein-invoices" />
      <?php if ( $status ) : ?><input type="hidden" name="status" value="<?php echo esc_attr($status); ?>" /><?php endif; ?>
      <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search by invoice # or client name…" class="ezy-search-input" />
      <button type="submit" class="button">Search</button>
      <?php if ( $search ) : ?><a href="?page=ezyein-invoices<?php echo $status ? '&status=' . esc_attr( $status ) : ''; ?>" class="button">Clear</a><?php endif; ?>
    </form>

    <p class="ezy-count"><?php echo (int) $total; ?> invoice(s)</p>

    <table class="wp-list-table widefat fixed striped ezy-table">
      <thead><tr>
        <th>Invoice #</th><th>Client</th><th>Issue Date</th><th>Due Date</th>
        <th>Amount</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if ( empty( $invoices ) ) : ?>
        <tr><td colspan="7" class="ezy-empty">No invoices found. <a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-create-invoice') ); ?>">Create your first invoice</a>.</td></tr>
      <?php else : ?>
        <?php foreach ( $invoices as $inv ) : ?>
        <?php
            $is_overdue = $inv->status === 'sent' && strtotime( $inv->due_date ) < time();
            $display_status = $is_overdue ? 'overdue' : $inv->status;
        ?>
        <tr>
          <td><strong><a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-invoice-view&id='.$inv->id) ); ?>"><?php echo esc_html( $inv->invoice_number ); ?></a></strong></td>
          <td>
            <?php echo esc_html( $inv->contact_name ); ?>
            <?php if ( $inv->company_name ) echo '<br><small>' . esc_html( $inv->company_name ) . '</small>'; ?>
          </td>
          <td><?php echo esc_html( gmdate( $fmt, strtotime( $inv->issue_date ) ) ); ?></td>
          <td><?php echo esc_html( gmdate( $fmt, strtotime( $inv->due_date ) ) ); ?></td>
          <td><strong><?php echo esc_html( $currency . ' ' . number_format( (float) $inv->total, 2 ) ); ?></strong></td>
          <td><span class="ezy-badge ezy-badge-<?php echo esc_attr( $display_status ); ?>"><?php echo esc_html( ucfirst( $display_status ) ); ?></span></td>
          <td class="ezy-actions">
            <a href="<?php echo esc_url( admin_url('admin.php?page=ezyein-invoice-view&id='.$inv->id) ); ?>" class="button button-small">View</a>
            <button class="button button-small ezy-resend-invoice" data-id="<?php echo (int)$inv->id; ?>" title="Resend email">Resend</button>
            <?php if ( $inv->status !== 'paid' ) : ?>
            <button class="button button-small ezy-mark-paid" data-id="<?php echo (int)$inv->id; ?>">Mark Paid</button>
            <?php endif; ?>
            <button class="button button-small button-link-delete ezy-delete-invoice" data-id="<?php echo (int)$inv->id; ?>" data-number="<?php echo esc_attr($inv->invoice_number); ?>">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>

    <?php if ( $pages > 1 ) : ?>
    <div class="ezy-pagination">
      <?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
        <a href="?page=ezyein-invoices&paged=<?php echo (int) $i; ?><?php echo $status ? '&status=' . esc_attr( $status ) : ''; ?><?php echo $search ? '&s='.urlencode($search) : ''; ?>"
           class="button<?php echo $i === $paged ? ' button-primary' : ''; ?>"><?php echo (int) $i; ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
