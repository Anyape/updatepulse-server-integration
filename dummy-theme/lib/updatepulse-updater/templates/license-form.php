<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap-license" data-package_slug="<?php echo esc_attr( $package_slug ); ?>" id="<?php echo esc_attr( 'wrap_license_' . $package_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'license_nonce' ) ); ?>">
	<p class="license-message<?php echo ( ! $show_license ) ? esc_attr( ' hidden' ) : ''; ?>">
		<span class="current-license-error hidden"></span> <span class="current-license-label"><?php esc_html_e( 'Current license key:', 'updatepulse-updater' ); ?></span> <span class="current-license"><?php echo esc_html( $license ); ?></span>
	</p>
	<div class="license-change">
		<label><?php esc_html_e( 'License key:', 'updatepulse-updater' ); ?></label> <input class="regular-text license" type="text" id="<?php echo esc_attr( 'license_key_' . $package_id ); ?>" value="<?php echo ( $show_license ) ? esc_html( $license ) : ''; ?>" >
		<span class="activate-license-container<?php echo ( $show_license ) ? ' hidden' : ''; ?>">
			<input type="button" value="<?php esc_attr_e( 'Activate', 'updatepulse-updater' ); ?>" class="button-primary activate-license" />
		</span>
		<span class="deactivate-license-container">
			<input data-next_deactivate="<?php echo esc_attr( $next_deactivate ); ?>" data-date_format="<?php echo esc_attr( $date_format ); ?>" type="button" value="<?php echo esc_html( $deactivate_text ); ?>" class="button deactivate-license" <?php echo ( $may_deactivate ) ? '' : 'disabled'; ?> />
		</span>
	</div>
</div>
