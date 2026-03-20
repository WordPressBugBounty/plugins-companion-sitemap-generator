<?php

// Database
global $wpdb;
$table_name = sprintf( "%s%s", $wpdb->prefix, CSG_DB_TABLE );

// Get the available frequency options
$schedule_options = csg_wp_get_schedules();
$sitemap_schedule = wp_get_schedule( 'csg_create_sitemap' );

// Create a sitemap
if( isset( $_POST['csg_create_sitemap'] ) ) {
	check_admin_referer( 'csg_create_sitemap__nonce' );
	csg_create_file( 'sitemap.xml' );
	$reload_url = esc_url( admin_url( 'tools.php?page=csg-sitemap' ) );
	header( "Location: {$reload_url}&updated=sitemap" );
}

// Update sitemap
if( isset( $_POST['csg_update_sitemap'] ) ) {
	check_admin_referer( 'csg_update_sitemap__nonce' );
	echo csg_write_file( 'sitemap.xml', csg_output_xml() );
}

// Edit
if( isset( $_POST['csg_save_robots'] ) ) {
	check_admin_referer( 'csg_robots__nonce' );
	echo csg_write_file( 'robots.txt', sanitize_textarea_field( $_POST['csg_robots_content'] ) );
}

// Create
if( isset( $_POST['csg_create_robots'] ) ) {
	check_admin_referer( 'csg_robots__nonce' );
	csg_create_file( 'robots.txt' );
	$reload_url = esc_url( admin_url( 'tools.php?page=csg-sitemap&updated=robots' ) );
	header( "Location: {$reload_url}" );
}

// Dismiss the "We couldn't find an HTML sitemap nag
if( isset( $_POST['dismiss_html_nag'] ) ) {
	check_admin_referer( 'dismiss_html_nag__nonce' );
	$query = $wpdb->query( "UPDATE {$table_name} SET onoroff = 'on' WHERE name = 'html_nag_dismissed'" );
	if( $query ) {
		wp_admin_notice( esc_html__( 'Settings saved', 'companion-sitemap-generator' ), [ "type" => "success" ] );
	} else {
		wp_admin_notice( esc_html__( 'One or more errors occured', 'companion-sitemap-generator' ), [ "type" => "error" ] );
	}
}

// Update database
if( isset( $_POST['csg_update_database'] ) ) {
	check_admin_referer( 'csg_update_database__nonce' );
	csg_update_db_check();
	echo '<div class="notice notice-success"><p>Database update completed!</p></div>';
}

// Save settings
if( isset( $_POST['csg_save_settings'] ) ) {

	check_admin_referer( 'csg_save_settings__nonce' );

	// Save frequency
	$has_errors 			= false;

	$csg_form_fields 		= array( 'frequency', 'use_sitemap_stylesheet', 'sitemap_stylesheet', 'ping_google', 'ping_bing', 'ping_yandex' );
	$csg_form_fields[] 		= 'xml_in_html'; // This setting got removed, keeping it here for now (will set to false on submit) until a later version, where it'll be removed entirely

	foreach( $csg_form_fields as $field ) {

		$field 			= esc_attr( $field );
		$sanitize 		= isset( $_POST[$field] ) ? sanitize_text_field( $_POST[$field] ) : '';
		$update_query 	= $wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET onoroff = %s WHERE name = %s", $sanitize, $field ) );

		if( ! empty( $wpdb->last_error ) ) {
			echo '<div class="notice notice-error"><p>'.$field.' '.esc_html( $wpdb->last_error ).'</p></div>';
			$has_errors = true;
		}

	}

	// Save schedule
	$sitemap_sc = sanitize_text_field( $_POST['sitemap_schedule'] );

	// First clear schedule
	wp_clear_scheduled_hook( 'csg_create_sitemap' );

	// Then set the new times
	if( $sitemap_sc !== 'never' ) {
		wp_schedule_event( time(), $sitemap_sc, 'csg_create_sitemap' );
	}

	// Show notice
	if( ! $has_errors ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved', 'companion-sitemap-generator' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'One or more errors occured', 'companion-sitemap-generator' ) . '</p></div>';
	}

}

// This message shouldn't be needed but is just here as a fallback
if( ! csg_db_version_matches() ) {
	echo '<form method="POST" action="" class="notice notice-warning noticable-notice">';
		wp_nonce_field( 'csg_update_database__nonce' );
		echo esc_html( sprintf(
			"<p>Please update your database from version %s to the latest version %s</p>",
			get_site_option( "csg_db_version" ),
			CSG_DB_VERSION
		) );
		echo '<button type="submit" name="csg_update_database" class="button">Update</button>';
	echo '</form>';
}

// If there's no XML file yet
if( ! csg_get_sitemap() ) {
	echo '<form method="POST" action="" class="notice notice-warning noticable-notice">';
		wp_nonce_field( 'csg_create_sitemap__nonce' );
		echo '<p>'.sprintf( esc_html__( 'Please generate a %s file first', 'companion-sitemap-generator' ), strtolower( esc_html__( 'Sitemap', 'companion-sitemap-generator' ) ) ).'</p>';
		echo '<p><button type="submit" name="csg_create_sitemap" class="button button-primary">'.sprintf( esc_html__( 'Generate %s', 'companion-sitemap-generator' ), esc_html__( 'Sitemap', 'companion-sitemap-generator' ) ).'</button></p>';
	echo '</form>';
	echo '<style>.welcome-card { display:none!important; content-visibility: hidden; }</style>';
}

// If we couldn't find an HTML page
if( ! csg_get_sitemap_page() ) {
	echo '<form method="POST" action="" class="notice notice-warning noticable-notice">';
		wp_nonce_field( 'dismiss_html_nag__nonce' );
		echo '<p>'.sprintf( esc_html__( "It looks like you're not using the HTML sitemap on your site", 'companion-sitemap-generator' ) ).'.<br />'.sprintf( esc_html__( "Both search engines and users will benefit from having an HTML sitemap on your website", 'companion-sitemap-generator' ) ).'.</p>';
		echo '<p><button type="submit" name="dismiss_html_nag" class="button">'.esc_html__( 'Dismiss', 'companion-sitemap-generator' ).'</button></p>';
	echo '</form>';
}

?>

<h2 id="sitemap__header" style="margin-top: 2rem;">
	<?php echo esc_html__( 'Sitemap', 'companion-sitemap-generator' ); ?>
</h2>

<form method="POST" action="" class="card welcome-card">

	<?php wp_nonce_field( "csg_update_sitemap__nonce" ); ?>

	<p>
		<?php /* translators: interval, like "daily" or "weekly"" */
		echo sprintf( esc_html__(
			"We'll update your sitemap %s, but in case you'd like to update it manually you can do that here.",
			"companion-sitemap-generator"
		), $sitemap_schedule ); ?>
	</p>

	<button type="submit" name="csg_update_sitemap" class="button">
		<?php echo sprintf(
			esc_html__( 'Update %s', 'companion-sitemap-generator' ),
			esc_html__( 'Sitemap', 'companion-sitemap-generator' )
		); ?>
	</button>

	<a href="<?php echo esc_url( csg_get_file_url( 'sitemap' ) ); ?>" class="button button-blank" target="_blank">
		<?php echo sprintf(
			esc_html__( 'View %s', 'companion-sitemap-generator' ),
			esc_html__( 'Sitemap', 'companion-sitemap-generator' )
		); ?>
	</a>

</form>

<form method="POST" action="">

	<?php wp_nonce_field( "csg_save_settings__nonce" ); ?>

	<section id="sitemap" aria-labeledby="sitemap__header" class="card">

		<h3 class="title" style="display: flex; align-items: center;">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 14.5c-3.6 0-6.5-2.9-6.5-6.5S8.4 5.5 12 5.5s6.5 2.9 6.5 6.5-2.9 6.5-6.5 6.5zM9 16l4.5-3L15 8.4l-4.5 3L9 16z"></path></svg>
			<?php echo sprintf(
				esc_html__( '%s settings', 'companion-sitemap-generator' ),
				esc_html__( 'Search engine', 'companion-sitemap-generator' )
			); ?>
		</h3>

		<div class="form-fields">

			<div>
				<label for="ping_searchengines" class="form-fields-label">
					<?php echo esc_html__( 'Sitemap submission', 'companion-sitemap-generator' ); ?>
				</label>
				<p class="description">
					<?php echo esc_html__( 'We can notify search engines when changes are made to your sitemap', 'companion-sitemap-generator' ); ?>.
				</p>
			</div>

			<div>
				<fieldset name="ping_searchengines">
					<?php
					foreach( csg_get_supported_engines() as $engine => $ping_url ) {
						echo sprintf(
							'<p><label for="ping_%1$s">
								<input type="checkbox" id="ping_%1$s" name="ping_%1$s" %3$s>
								%2$s
							</label></p>',
							sanitize_key( $engine ),
							esc_html( $engine ),
							csg_get_option( sprintf( "ping_%s", sanitize_key( $engine ) ) ) ? "CHECKED" : ""
						);
					}
					?>
				</fieldset>
			</div>
		</div>

		<div class="form-fields">
			<div>
				<label for="frequency" class="form-fields-label">
					<?php echo esc_html__( 'Frequency', 'companion-sitemap-generator' ); ?>
				</label>
				<p class="description">
					<?php echo esc_html__( 'How frequently the content of your site is likely to change.', 'companion-sitemap-generator' ); ?>
				</p>
			</div>
			<div>
				<?php
				$frequency_options 	= csg_frequency_options();
				$set_frequency 		= csg_get_option( 'frequency' );
				echo '<p><select name="frequency" id="frequency" class="form-fields-option">';
				foreach( $frequency_options as $frequency_key => $frequency_label ) {
					$freq_key 		= esc_html( $frequency_key );
					$freq_label 	= esc_html( $frequency_label );
					$is_checked 	= ( $set_frequency  === $freq_key ) ? "SELECTED" : "";
					echo "<option value='{$freq_key}' {$is_checked}>{$freq_label}</option>";
				}
				echo '</select></p>';
				?>

				<p class="description">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1lh" height="1lh" aria-hidden="true" focusable="false" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 12a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0ZM12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm.75 4v1.5h-1.5V8h1.5Zm0 8v-5h-1.5v5h1.5Z"></path></svg>
					<?php echo esc_html__( 'This value provides general information to search engines and may not correlate exactly to how often they crawl the page.', 'companion-sitemap-generator' ); ?>
				</p>
			</div>
		</div>

		<hr />

		<h3 class="title" style="display: flex; align-items: center;">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" d="M8.95 11.25H4v1.5h4.95v4.5H13V18c0 1.1.9 2 2 2h3c1.1 0 2-.9 2-2v-3c0-1.1-.9-2-2-2h-3c-1.1 0-2 .9-2 2v.75h-2.55v-7.5H13V9c0 1.1.9 2 2 2h3c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3c-1.1 0-2 .9-2 2v.75H8.95v4.5ZM14.5 15v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5h-3c-.3 0-.5.2-.5.5Zm0-6V6c0-.3.2-.5.5-.5h3c.3 0 .5.2.5.5v3c0 .3-.2.5-.5.5h-3c-.3 0-.5-.2-.5-.5Z" clip-rule="evenodd"></path></svg>
			<?php echo sprintf( esc_html__( '%s settings', 'companion-sitemap-generator' ), esc_html__( 'Sitemap', 'companion-sitemap-generator' ) ); ?>
		</h3>

		<div class="form-fields">
			<div>
				<label for="sitemap_schedule" class="form-fields-label"><?php echo esc_html__( 'Auto updating', 'companion-sitemap-generator' ); ?></label>
				<p class="description"><?php echo esc_html__( 'How often should the sitemap be updated? You can always update it manually.', 'companion-sitemap-generator' ); ?></p>
			</div>
			<div>
				<?php
				echo '<p><select name="sitemap_schedule" id="sitemap_schedule">';
				foreach( $schedule_options as $schedule_key => $schedule_label ) {
					$sched_key 		= esc_html( $schedule_key );
					$sched_label 	= esc_html( $schedule_label );
					$is_checked 	= ( $sitemap_schedule  === $sched_key ) ? "SELECTED" : "";
					echo "<option value='{$sched_key}' {$is_checked}>{$sched_label}</option>";
				}
				echo '</select></p>';

				?>
				<p class="description">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1lh" height="1lh" aria-hidden="true" focusable="false" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 12a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0ZM12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm.75 4v1.5h-1.5V8h1.5Zm0 8v-5h-1.5v5h1.5Z"></path></svg>
					<?php echo esc_html__( 'Settings only apply to the XML sitemap, the HTML sitemap is always up-to-date', 'companion-sitemap-generator' ); ?>.
				</p>
			</div>
		</div>

		<div class="form-fields">
			<div>
				<label class="form-fields-label"><?php echo esc_html__( 'Sitemap styling', 'companion-sitemap-generator' ); ?></label>
				<p class="description"><?php echo esc_html__( 'You can apply custom styling to your XML sitemap and make it easier to read for humans', 'companion-sitemap-generator' ); ?>.</p>
			</div>
			<div>
				<p>
					<input type="checkbox" name="use_sitemap_stylesheet" id="use_sitemap_stylesheet" class="toggle-form-fields" <?php if( csg_option_enabled( 'use_sitemap_stylesheet' ) ) { echo "CHECKED"; } ?>>
					<label for="use_sitemap_stylesheet"><?php echo sprintf( esc_html__( 'Enable %s', 'companion-sitemap-generator' ), strtolower( esc_html__( 'Sitemap styling', 'companion-sitemap-generator' ) ) ); ?></label>
				</p>

				<p class="form-fields-option has-clear-button">
					<input class="text" type="text" name="sitemap_stylesheet" id="sitemap_stylesheet" placeholder="<?php echo csg_xml_default_styling(); ?>" value="<?php echo csg_get_xml_stylesheet( false ); ?>">
					<a id="csg-default-stylesheet" class="button clear-button" role="button" style="display: none;"><?php echo esc_html__( 'Default', 'companion-sitemap-generator' ); ?></a>
					<script type="text/javascript">jQuery( "#csg-default-stylesheet" ).show(); jQuery( "#csg-default-stylesheet" ).click( function( e ) { jQuery( '#sitemap_stylesheet' ).val(''); } );</script>
				</p>
			</div>
		</div>

		<?php echo '<p><button type="submit" name="csg_save_settings" class="button button-primary">'.esc_html__( 'Save Changes' ).'</button></p>'; ?>

	</section>

</form>

<h2 id="robots__header" style="margin-top: 2rem;">
	<?php echo esc_html__( 'Robots', 'companion-sitemap-generator' ); ?>
</h2>

<form method="POST" action="" id="robots" labeledby="robots__header" class="card">

	<div class="form-fields">
		<div>
			<label class="form-fields-label"><?php echo esc_html__( 'Edit Robots', 'companion-sitemap-generator' ); ?></label>
			<p class="description"><?php echo esc_html__( 'Prevent search engines from indexing certain parts of your site by adding them to your robots.txt file.', 'companion-sitemap-generator' ); ?>.</p>

			<?php

			wp_nonce_field( 'csg_robots__nonce' );

			$example_content = csg_default_robots();

			// If no robots file exists yet
			if( ! csg_get_robots() ) {

				echo '<p class="description">'.sprintf( esc_html__( 'Please generate a %s file first', 'companion-sitemap-generator' ), 'robots' ).'.</p>';

				echo '<p><textarea name="csg_robots_content" id="csg_robots_content" rows="15" disabled>'.$example_content.'</textarea></p>';
				echo '<p><button type="submit" name="csg_create_robots" class="button button-primary">'.sprintf( esc_html__( 'Generate %s', 'companion-sitemap-generator' ), 'robots.txt' ).'</button></p>';

			// Show the editor
			} else {

				echo '<textarea name="csg_robots_content" id="csg_robots_content" rows="15">'.csg_read_file( 'robots.txt' ).'</textarea>';
				echo '<p><button type="submit" name="csg_save_robots" class="button button-primary">'.esc_html__( 'Save Changes' ).'</button></p>';

			}

			?>
		</div>

		<div>
			<label class="form-fields-label">
				<?php echo esc_html__( 'Basic Example', 'companion-sitemap-generator' ); ?>
			</label>

			<p class="description">
				<?php echo esc_html__( "Don't know where to start? Here's a basic example of what a robots file could look like.", "companion-sitemap-generator" ); ?>.
			</p>

			<textarea rows="15" disabled style="border-color: #8c8f94; color: #2c3338;"><?php echo $example_content; ?></textarea>
		</div>

	</div>

</form>
