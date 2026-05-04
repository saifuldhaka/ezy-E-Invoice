<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

if ( isset( $_POST['ezy_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezy_settings_nonce'] ) ), 'ezy_save_settings' ) ) {
    foreach ( Ezy_Settings::sections() as $section_id => $section ) {
        foreach ( $section['fields'] as $key => $field ) {
            $opt_key = 'ezy_invoice_' . $key;
            if ( $field['type'] === 'checkbox' ) {
                update_option( $opt_key, isset( $_POST[ $opt_key ] ) ? '1' : '' );
            } elseif ( $field['type'] === 'textarea' ) {
                update_option( $opt_key, sanitize_textarea_field( wp_unslash( $_POST[ $opt_key ] ?? '' ) ) );
            } elseif ( $field['type'] === 'number' ) {
                update_option( $opt_key, floatval( wp_unslash( $_POST[ $opt_key ] ?? 0 ) ) );
            } elseif ( $field['type'] === 'email' ) {
                update_option( $opt_key, sanitize_email( wp_unslash( $_POST[ $opt_key ] ?? '' ) ) );
            } elseif ( $field['type'] === 'url' ) {
                update_option( $opt_key, esc_url_raw( wp_unslash( $_POST[ $opt_key ] ?? '' ) ) );
            } else {
                update_option( $opt_key, sanitize_text_field( wp_unslash( $_POST[ $opt_key ] ?? '' ) ) );
            }
        }
    }
    echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved successfully!</strong></p></div>';
}

$sections = Ezy_Settings::sections();
$active   = sanitize_text_field( wp_unslash( $_GET['tab'] ?? 'company' ) );
?>
<div class="wrap ezy-wrap">
  <div class="ezy-header">
    <h1><span class="dashicons dashicons-admin-settings"></span> ezy E Invoice — Settings</h1>
  </div>

  <nav class="nav-tab-wrapper ezy-tabs">
    <?php foreach ( $sections as $sid => $sec ) : ?>
    <a href="?page=ezy-settings&tab=<?php echo esc_attr( $sid ); ?>"
       class="nav-tab <?php echo $active === $sid ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons <?php echo esc_attr( $sec['icon'] ); ?>"></span>
      <?php echo esc_html( $sec['title'] ); ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <form method="post" action="">
    <?php wp_nonce_field( 'ezy_save_settings', 'ezy_settings_nonce' ); ?>
    <?php foreach ( $sections as $sid => $sec ) : ?>
    <?php if ( $active !== $sid ) continue; ?>
    <div class="ezy-card ezy-settings-section">
      <h2><span class="dashicons <?php echo esc_attr( $sec['icon'] ); ?>"></span> <?php echo esc_html( $sec['title'] ); ?></h2>
      <table class="form-table ezy-form-table">
        <?php foreach ( $sec['fields'] as $key => $field ) : ?>
        <?php
            $opt_key  = 'ezy_invoice_' . $key;
            $value    = get_option( $opt_key, $field['default'] ?? '' );
            $required = ! empty( $field['required'] ) ? 'required' : '';
            $step     = $field['step'] ?? '1';
        ?>
        <tr>
          <th scope="row"><label for="<?php echo esc_attr( $opt_key ); ?>"><?php echo esc_html( $field['label'] ); ?><?php if ( $required ) echo ' <span class="ezy-required">*</span>'; ?></label></th>
          <td>
            <?php if ( $field['type'] === 'textarea' ) : ?>
              <textarea id="<?php echo esc_attr( $opt_key ); ?>" name="<?php echo esc_attr( $opt_key ); ?>"
                        rows="4" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
            <?php elseif ( $field['type'] === 'checkbox' ) : ?>
              <label>
                <input type="checkbox" id="<?php echo esc_attr( $opt_key ); ?>"
                       name="<?php echo esc_attr( $opt_key ); ?>" value="1" <?php checked( $value, '1' ); ?> />
                Enable
              </label>
            <?php elseif ( $field['type'] === 'number' ) : ?>
              <input type="number" id="<?php echo esc_attr( $opt_key ); ?>"
                     name="<?php echo esc_attr( $opt_key ); ?>" value="<?php echo esc_attr( $value ); ?>"
                     class="small-text" step="<?php echo esc_attr( $step ); ?>" min="0" <?php echo esc_attr( $required ); ?> />
            <?php else : ?>
              <input type="<?php echo esc_attr( $field['type'] ); ?>"
                     id="<?php echo esc_attr( $opt_key ); ?>"
                     name="<?php echo esc_attr( $opt_key ); ?>"
                     value="<?php echo esc_attr( $value ); ?>"
                     class="regular-text" <?php echo esc_attr( $required ); ?> />
            <?php endif; ?>
            <?php if ( ! empty( $field['desc'] ) ) : ?>
            <p class="description"><?php echo esc_html( $field['desc'] ); ?></p>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endforeach; ?>
    <p class="submit"><input type="submit" class="button button-primary button-large" value="Save Settings" /></p>
  </form>
</div>
