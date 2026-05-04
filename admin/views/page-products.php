<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

$search   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$paged    = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$perpage  = 20;
$offset   = ( $paged - 1 ) * $perpage;
$total    = EZYEIN_DB::count_products( $search );
$products = EZYEIN_DB::get_products( [ 'search' => $search, 'limit' => $perpage, 'offset' => $offset ] );
$pages    = ceil( $total / $perpage );
$currency = EZYEIN_Settings::get( 'currency_symbol', 'RM' );
$wc_active = class_exists( 'WooCommerce' );
?>
<div class="wrap ezy-wrap">
  <div class="ezy-header">
    <h1><span class="dashicons dashicons-cart"></span> Products
      <button class="button button-primary ezy-btn-add" data-modal="product-modal">+ Add Product</button>
      <?php if ( $wc_active ) : ?>
      <button class="button ezy-sync-wc" id="sync-wc-btn">
        <span class="dashicons dashicons-update"></span> Sync from WooCommerce
      </button>
      <?php endif; ?>
    </h1>
  </div>

  <div class="ezy-card">
    <form method="get" action="" class="ezy-search-form">
      <input type="hidden" name="page" value="ezyein-products" />
      <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search products…" class="ezy-search-input" />
      <button type="submit" class="button">Search</button>
      <?php if ( $search ) : ?><a href="?page=ezyein-products" class="button">Clear</a><?php endif; ?>
    </form>

    <p class="ezy-count"><?php echo (int) $total; ?> product(s) found</p>

    <table class="wp-list-table widefat fixed striped ezy-table">
      <thead><tr>
        <th>Name</th><th>SKU</th><th>Price</th><th>Unit</th><th>WC Linked</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if ( empty( $products ) ) : ?>
        <tr><td colspan="6" class="ezy-empty">No products found. <a href="#" class="ezy-btn-add" data-modal="product-modal">Add your first product</a>.</td></tr>
      <?php else : ?>
        <?php foreach ( $products as $p ) : ?>
        <tr>
          <td><strong><?php echo esc_html( $p->name ); ?></strong><?php if ( $p->description ) echo '<br><small>' . esc_html( wp_trim_words( $p->description, 10 ) ) . '</small>'; ?></td>
          <td><?php echo esc_html( $p->sku ); ?></td>
          <td><?php echo esc_html( $currency . ' ' . number_format( (float) $p->price, 2 ) ); ?></td>
          <td><?php echo esc_html( $p->unit ); ?></td>
          <td><?php echo $p->wc_product_id ? '<span class="ezy-badge ezy-badge-green">WC #' . (int)$p->wc_product_id . '</span>' : '—'; ?></td>
          <td class="ezy-actions">
            <button class="button button-small ezy-edit-product" data-id="<?php echo (int) $p->id; ?>">Edit</button>
            <button class="button button-small button-link-delete ezy-delete-product" data-id="<?php echo (int) $p->id; ?>" data-name="<?php echo esc_attr( $p->name ); ?>">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Product Modal -->
<div id="product-modal" class="ezy-modal" style="display:none;">
  <div class="ezy-modal-content">
    <div class="ezy-modal-header">
      <h2 id="product-modal-title">Add Product</h2>
      <button class="ezy-modal-close">&times;</button>
    </div>
    <form id="product-form" class="ezy-modal-body">
      <input type="hidden" name="id" id="product_id" value="0" />
      <div class="ezy-form-grid">
        <div class="ezy-form-field ezy-field-full">
          <label>Product / Service Name <span class="ezy-required">*</span></label>
          <input type="text" name="name" id="pf_name" required class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>SKU / Code</label>
          <input type="text" name="sku" id="pf_sku" class="regular-text" />
        </div>
        <div class="ezy-form-field">
          <label>Unit</label>
          <input type="text" name="unit" id="pf_unit" value="unit" class="regular-text" placeholder="unit, hour, pcs, kg…" />
        </div>
        <div class="ezy-form-field">
          <label>Unit Price (<?php echo esc_html( $currency ); ?>) <span class="ezy-required">*</span></label>
          <input type="number" name="price" id="pf_price" required step="0.01" min="0" class="regular-text" />
        </div>
        <?php if ( $wc_active ) : ?>
        <div class="ezy-form-field">
          <label>Link to WooCommerce Product</label>
          <select name="wc_product_id" id="pf_wc_product_id" class="regular-text">
            <option value="">— None —</option>
            <?php
            $wc_products = get_posts( [ 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish' ] );
            foreach ( $wc_products as $wcp ) echo '<option value="' . (int)$wcp->ID . '">' . esc_html( $wcp->post_title ) . '</option>';
            ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="ezy-form-field ezy-field-full">
          <label>Description</label>
          <textarea name="description" id="pf_description" rows="3" class="large-text"></textarea>
        </div>
      </div>
      <div class="ezy-modal-footer">
        <button type="submit" class="button button-primary button-large" id="save-product-btn">Save Product</button>
        <button type="button" class="button ezy-modal-close">Cancel</button>
        <span class="ezy-spinner spinner"></span>
        <span class="ezy-form-msg"></span>
      </div>
    </form>
  </div>
</div>
