<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial included inside class method; variables are not in global scope.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

$search  = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$paged   = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$perpage = 20;
$offset  = ( $paged - 1 ) * $perpage;
$total   = EZYEIN_DB::count_clients( $search );
$clients = EZYEIN_DB::get_clients( [ 'search' => $search, 'limit' => $perpage, 'offset' => $offset ] );
$pages   = ceil( $total / $perpage );
?>
<div class="wrap ezy-wrap">
  <div class="ezy-header">
    <h1><span class="dashicons dashicons-admin-users"></span> Clients
      <button class="button button-primary ezy-btn-add" data-modal="client-modal">+ Add Client</button>
    </h1>
  </div>

  <div class="ezy-card">
    <form method="get" action="" class="ezy-search-form">
      <input type="hidden" name="page" value="ezyein-clients" />
      <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search clients…" class="ezy-search-input" />
      <button type="submit" class="button">Search</button>
      <?php if ( $search ) : ?><a href="?page=ezyein-clients" class="button">Clear</a><?php endif; ?>
    </form>

    <p class="ezy-count"><?php echo (int) $total; ?> client(s) found</p>

    <table class="wp-list-table widefat fixed striped ezy-table">
      <thead><tr>
        <th>Contact Name</th><th>Company</th><th>Email</th><th>Phone</th>
        <th>City</th><th>Tax No.</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if ( empty( $clients ) ) : ?>
        <tr><td colspan="7" class="ezy-empty">No clients found. <a href="#" class="ezy-btn-add" data-modal="client-modal">Add your first client</a>.</td></tr>
      <?php else : ?>
        <?php foreach ( $clients as $c ) : ?>
        <tr>
          <td><strong><?php echo esc_html( $c->contact_name ); ?></strong></td>
          <td><?php echo esc_html( $c->company_name ); ?></td>
          <td><a href="mailto:<?php echo esc_attr( $c->email ); ?>"><?php echo esc_html( $c->email ); ?></a></td>
          <td><?php echo esc_html( $c->phone ); ?></td>
          <td><?php echo esc_html( $c->city ); ?></td>
          <td><?php echo esc_html( $c->tax_number ); ?></td>
          <td class="ezy-actions">
            <button class="button button-small ezy-edit-client" data-id="<?php echo (int) $c->id; ?>">Edit</button>
            <button class="button button-small button-link-delete ezy-delete-client" data-id="<?php echo (int) $c->id; ?>" data-name="<?php echo esc_attr( $c->contact_name ); ?>">Delete</button>
            <a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ezyein-create-invoice&client_id=' . $c->id ), 'ezyein_create_invoice' ) ); ?>">New Invoice</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>

    <?php if ( $pages > 1 ) : ?>
    <div class="ezy-pagination">
      <?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
        <a href="?page=ezyein-clients&paged=<?php echo (int) $i; ?><?php echo $search ? '&s=' . urlencode($search) : ''; ?>"
           class="button<?php echo $i === $paged ? ' button-primary' : ''; ?>"><?php echo (int) $i; ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Client Modal -->
<div id="client-modal" class="ezy-modal" style="display:none;">
  <div class="ezy-modal-content">
    <div class="ezy-modal-header">
      <h2 id="client-modal-title">Add Client</h2>
      <button class="ezy-modal-close">&times;</button>
    </div>
    <form id="client-form" class="ezy-modal-body">
      <input type="hidden" name="id" id="client_id" value="0" />
      <div class="ezy-form-grid">
        <div class="ezy-form-field">
          <label>Contact Name <span class="ezy-required">*</span></label>
          <input type="text" name="contact_name" id="cf_contact_name" required class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>Company Name</label>
          <input type="text" name="company_name" id="cf_company_name" class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>Email Address <span class="ezy-required">*</span></label>
          <input type="email" name="email" id="cf_email" required class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>Phone</label>
          <input type="text" name="phone" id="cf_phone" class="regular-text" />
        </div>
        <div class="ezy-form-field ezy-field-full">
          <label>Address Line 1</label>
          <input type="text" name="address_line1" id="cf_address_line1" class="regular-text" />
        </div>
        <div class="ezy-form-field ezy-field-full">
          <label>Address Line 2</label>
          <input type="text" name="address_line2" id="cf_address_line2" class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>City</label>
          <input type="text" name="city" id="cf_city" class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>State / Province</label>
          <input type="text" name="state_province" id="cf_state_province" class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>Country</label>
          <input type="text" name="country" id="cf_country" class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>Postal Code</label>
          <input type="text" name="postal_code" id="cf_postal_code" class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>Tax / GST No.</label>
          <input type="text" name="tax_number" id="cf_tax_number" class="regular-text" />
        </div>
        <div class="ezy-form-field ezy-field-full">
          <label>Notes</label>
          <textarea name="notes" id="cf_notes" rows="3" class="large-text"></textarea>
        </div>
      </div>
      <div class="ezy-modal-footer">
        <button type="submit" class="button button-primary button-large" id="save-client-btn">Save Client</button>
        <button type="button" class="button ezy-modal-close">Cancel</button>
        <span class="ezy-spinner spinner"></span>
        <span class="ezy-form-msg"></span>
      </div>
    </form>
  </div>
</div>
