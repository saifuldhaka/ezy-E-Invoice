<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

$currency        = Ezy_Settings::get( 'currency_symbol', 'RM' );
$tax_enabled     = Ezy_Settings::get( 'tax_enabled', '1' );
$tax_rate        = Ezy_Settings::get( 'tax_rate', 6 );
$tax_label       = Ezy_Settings::get( 'tax_label', 'SST' );
$sc_enabled      = Ezy_Settings::get( 'service_charge_enabled', '' );
$sc_rate         = Ezy_Settings::get( 'service_charge_rate', 10 );
$sc_label        = Ezy_Settings::get( 'service_charge_label', 'Service Charge' );
$discount_enabled= Ezy_Settings::get( 'discount_enabled', '' );
$default_notes   = Ezy_Settings::get( 'default_notes', '' );
$payment_terms   = (int) Ezy_Settings::get( 'payment_terms', 30 );
$next_number     = Ezy_DB::generate_invoice_number();
$issue_date      = gmdate('Y-m-d');
$due_date        = gmdate('Y-m-d', strtotime("+{$payment_terms} days"));
// Pre-select client if coming from client page
$preselect_client_id = absint( $_GET['client_id'] ?? 0 );
$preselect_client    = $preselect_client_id ? Ezy_DB::get_client( $preselect_client_id ) : null;
?>
<div class="wrap ezy-wrap">
  <div class="ezy-header">
    <h1><span class="dashicons dashicons-plus-alt2"></span> Create New Invoice</h1>
  </div>

  <div id="ezy-create-success" class="ezy-success-banner" style="display:none;">
    <span class="dashicons dashicons-yes-alt"></span>
    <strong id="ezy-success-msg"></strong>
    <a href="#" id="ezy-view-invoice-link" class="button button-primary" style="margin-left:10px;">View Invoice</a>
    <a href="<?php echo esc_url( admin_url('admin.php?page=ezy-create-invoice') ); ?>" class="button" style="margin-left:5px;">Create Another</a>
  </div>

  <form id="ezy-invoice-form">
    <div class="ezy-create-layout">

      <!-- LEFT: Invoice Header -->
      <div class="ezy-create-main">

        <!-- Client Selection -->
        <div class="ezy-card">
          <h2><span class="dashicons dashicons-admin-users"></span> Client</h2>
          <div class="ezy-client-search-wrap">
            <input type="text" id="client-search-input" placeholder="Type to search client…" class="ezy-autocomplete" autocomplete="off" <?php echo $preselect_client ? 'value="' . esc_attr( $preselect_client->contact_name . ( $preselect_client->company_name ? ' — ' . $preselect_client->company_name : '' ) ) . '"' : ''; ?> />
            <input type="hidden" name="client_id" id="client_id" value="<?php echo (int) $preselect_client_id; ?>" required />
          </div>
          <div id="client-info" class="ezy-client-info" <?php echo $preselect_client ? '' : 'style="display:none;"'; ?>>
            <?php if ( $preselect_client ) : ?>
            <div class="ezy-client-card">
              <strong><?php echo esc_html( $preselect_client->contact_name ); ?></strong>
              <?php if ( $preselect_client->company_name ) echo '<div>' . esc_html( $preselect_client->company_name ) . '</div>'; ?>
              <div><?php echo esc_html( $preselect_client->email ); ?></div>
              <?php if ( $preselect_client->phone ) echo '<div>' . esc_html( $preselect_client->phone ) . '</div>'; ?>
            </div>
            <?php endif; ?>
          </div>
          <p class="description">Can't find client? <a href="<?php echo esc_url( admin_url('admin.php?page=ezy-clients') ); ?>">Manage clients</a>.</p>
        </div>

        <!-- Invoice Items -->
        <div class="ezy-card">
          <h2><span class="dashicons dashicons-list-view"></span> Invoice Items</h2>
          <div class="ezy-items-table-wrap">
            <table class="ezy-items-table" id="invoice-items-table">
              <thead>
                <tr>
                  <th class="col-num">#</th>
                  <th class="col-product">Product / Service</th>
                  <th class="col-desc">Description</th>
                  <th class="col-price">Unit Price</th>
                  <th class="col-qty">Qty</th>
                  <th class="col-total">Total</th>
                  <th class="col-action"></th>
                </tr>
              </thead>
              <tbody id="items-body">
                <!-- JS will add rows here -->
              </tbody>
            </table>
          </div>
          <div class="ezy-items-footer">
            <button type="button" id="add-item-btn" class="button">
              <span class="dashicons dashicons-plus-alt2"></span> Add Item
            </button>
          </div>
        </div>

        <!-- Notes -->
        <div class="ezy-card">
          <h2><span class="dashicons dashicons-editor-paragraph"></span> Notes</h2>
          <textarea name="notes" id="invoice-notes" rows="4" class="large-text" placeholder="Payment terms, special instructions…"><?php echo esc_textarea( $default_notes ); ?></textarea>
        </div>
      </div>

      <!-- RIGHT: Invoice Meta + Totals -->
      <div class="ezy-create-sidebar">

        <!-- Invoice Details -->
        <div class="ezy-card">
          <h2><span class="dashicons dashicons-info-outline"></span> Invoice Details</h2>
          <table class="ezy-meta-table">
            <tr>
              <td><label>Invoice Number</label></td>
              <td><input type="text" name="invoice_number" id="invoice_number" value="<?php echo esc_attr( $next_number ); ?>" class="regular-text" required /></td>
            </tr>
            <tr>
              <td><label>Issue Date</label></td>
              <td><input type="date" name="issue_date" id="issue_date" value="<?php echo esc_attr( $issue_date ); ?>" required /></td>
            </tr>
            <tr>
              <td><label>Due Date</label></td>
              <td><input type="date" name="due_date" id="due_date" value="<?php echo esc_attr( $due_date ); ?>" required /></td>
            </tr>
          </table>
        </div>

        <!-- Tax & Charges -->
        <div class="ezy-card">
          <h2><span class="dashicons dashicons-calculator"></span> Charges</h2>
          <div class="ezy-charges">
            <label class="ezy-toggle">
              <input type="checkbox" id="tax-toggle" <?php checked( $tax_enabled, '1' ); ?> />
              <span>Enable <?php echo esc_html( $tax_label ); ?> (<?php echo esc_html( $tax_rate ); ?>%)</span>
            </label>
            <label class="ezy-toggle">
              <input type="checkbox" id="sc-toggle" <?php checked( $sc_enabled, '1' ); ?> />
              <span>Enable <?php echo esc_html( $sc_label ); ?> (<?php echo esc_html( $sc_rate ); ?>%)</span>
            </label>
            <?php if ( $discount_enabled ) : ?>
            <div class="ezy-discount-row">
              <label>Discount (<?php echo esc_html( $currency ); ?>)</label>
              <input type="number" id="discount-amount" name="discount_amount" value="0" min="0" step="0.01" />
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Totals -->
        <div class="ezy-card ezy-totals-card">
          <table class="ezy-totals-table">
            <tr>
              <td>Subtotal</td>
              <td id="display-subtotal"><?php echo esc_html($currency); ?> 0.00</td>
            </tr>
            <tr id="tax-row" <?php echo $tax_enabled ? '' : 'style="display:none"'; ?>>
              <td><?php echo esc_html( $tax_label ); ?> (<?php echo esc_html( $tax_rate ); ?>%)</td>
              <td id="display-tax"><?php echo esc_html($currency); ?> 0.00</td>
            </tr>
            <tr id="sc-row" <?php echo $sc_enabled ? '' : 'style="display:none"'; ?>>
              <td><?php echo esc_html( $sc_label ); ?> (<?php echo esc_html( $sc_rate ); ?>%)</td>
              <td id="display-sc"><?php echo esc_html($currency); ?> 0.00</td>
            </tr>
            <tr id="discount-row" style="display:none">
              <td>Discount</td>
              <td id="display-discount">-<?php echo esc_html($currency); ?> 0.00</td>
            </tr>
            <tr class="ezy-total-final">
              <td><strong>TOTAL</strong></td>
              <td id="display-total"><strong><?php echo esc_html($currency); ?> 0.00</strong></td>
            </tr>
          </table>
          <!-- Hidden fields -->
          <input type="hidden" id="hid-subtotal"  name="subtotal"              value="0" />
          <input type="hidden" id="hid-tax-rate"  name="tax_rate"              value="<?php echo esc_attr($tax_rate); ?>" />
          <input type="hidden" id="hid-tax-amt"   name="tax_amount"            value="0" />
          <input type="hidden" id="hid-sc-rate"   name="service_charge_rate"   value="<?php echo esc_attr($sc_rate); ?>" />
          <input type="hidden" id="hid-sc-amt"    name="service_charge_amount" value="0" />
          <input type="hidden" id="hid-total"     name="total"                 value="0" />
          <input type="hidden" id="hid-tax-en"    name="tax_enabled"           value="<?php echo $tax_enabled ? '1':'0'; ?>" />
          <input type="hidden" id="hid-sc-en"     name="service_charge_enabled" value="<?php echo $sc_enabled ? '1':'0'; ?>" />
        </div>

        <!-- Submit -->
        <div class="ezy-card ezy-submit-card">
          <button type="button" id="ezy-create-send-btn" class="button button-primary button-hero ezy-btn-submit">
            <span class="dashicons dashicons-email-alt"></span>
            Create &amp; Send Invoice
          </button>
          <p class="description" style="margin-top:8px;">The invoice PDF will be created and emailed to the client automatically.</p>
          <span class="ezy-spinner spinner" id="create-spinner"></span>
          <div id="create-error" class="ezy-error-msg" style="display:none;"></div>
        </div>

      </div>
    </div>
  </form>
</div>
