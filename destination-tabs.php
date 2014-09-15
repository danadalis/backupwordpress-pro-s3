<?php
defined( 'WPINC' ) or die;

$services = HMBKP_Services::get_services( $schedule ); ?>

<fieldset class="hmbkp-form">

	<legend><?php _e( 'Send a copy of your backups offsite', 'backupwordpress-pro-s3' ); ?></legend>

	<span class="howto"><?php _e( 'A copy of each backup will be uploaded to each of the destinations you setup below, old backups are not automatically deleted from remote services.', 'backupwordpress-pro-s3' ); ?></span>

	<div class="hmbkp-tabs">

		<ul class="subsubsub">

			<?php foreach ( HMBKP_Services::get_services( $schedule ) as $service ) :

				if ( ! $service->is_tab_visible )
					continue; ?>

				<li>
					<a href="#tabs-<?php echo esc_attr( strtolower( sanitize_title_with_dashes( $service->name ) ) ); ?>">
						
						<?php

						if ( $service->is_service_active() )
							echo esc_html( '&#10004;' );
						
						?>

						<?php echo esc_html( $service->name ); ?>

					</a>

				</li>

			<?php endforeach; ?>

		</ul>

		<?php 

		foreach ( HMBKP_Services::get_services( $schedule ) as $service ) :

			if ( ! $service->is_tab_visible )
				continue; 

		?>

			<div id="tabs-<?php echo esc_attr( strtolower( sanitize_title_with_dashes( $service->name ) ) ); ?>">

				<form method="post" class="hmbkp-form">

					<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />

					<?php $service->form(); ?>

					<p class="submit">

						<?php wp_nonce_field( 'hmbkp_schedule_submit_action', 'hmbkp_schedule_submit_nonce' ); ?>

						<button type="button" class="button-secondary hmbkp-colorbox-close"><?php _e( 'Close', 'backupwordpress-pro-s3' ); ?></button>
						
						<button type="submit" class="button-primary dest-settings-save"><?php _e( 'Save', 'backupwordpress-pro-s3' ); ?></button>
					
					</p>

				</form>

			</div>

		<?php endforeach; ?>

	</div>

</fieldset>