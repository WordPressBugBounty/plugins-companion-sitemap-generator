<?php

// Save settings
if( isset( $_POST['submit'] ) ) {

	check_admin_referer( 'csg_additionalpages' );

	// Save frequency
	global $wpdb;
	$table_name = sprintf( "%s%s", $wpdb->prefix, CSG_DB_TABLE );
	$additionalpages = isset( $_POST[ "additionalpages" ] ) ? trim( sanitize_textarea_field( $_POST[ "additionalpages" ] ) ) : "";

	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'additionalpages'", $additionalpages ) );

	if( ! empty( $wpdb->last_error ) ) {
		echo '<div class="notice notice-error"><p>'.esc_html( $wpdb->last_error ).'</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>'.__( 'Settings saved', 'companion-sitemap-generator' ).'.</p></div>';
	}

}

echo "<div style='height: 1rem;' aria-hidden='true'></div>";

echo "<h2 class='section-title'>".esc_html__( 'Additional pages', 'companion-sitemap-generator' )."</h2>";
echo "<p class='description'>".esc_html__( 'Add pages to the sitemap in addition to your regular WordPress pages', 'companion-sitemap-generator' ).".</p>";

?>

<form method='POST' action='' class='card'>

	<?php wp_nonce_field( 'csg_additionalpages' ); ?>

	<div class="form-fields">
		<div>
			<label class="form-fields-label"><?php echo esc_html__( 'Additional pages', 'companion-sitemap-generator' ); ?></label>
			<p class="description"><?php echo esc_html__( 'Each URL should be added on a new line', 'companion-sitemap-generator' ); ?>.</p>
		</div>
		<div>

			<textarea name="additionalpages" rows="15"><?php echo esc_textarea( csg_get_option( 'additionalpages' ) ); ?></textarea>

			<p class="description">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1lh" height="1lh" aria-hidden="true" focusable="false" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 12a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0ZM12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm.75 4v1.5h-1.5V8h1.5Zm0 8v-5h-1.5v5h1.5Z"></path></svg>
				<?php echo esc_html__( 'Invalid URLs will not be added to the sitemap', 'companion-sitemap-generator' ); ?>.
			</p>

			<?php submit_button(); ?>

		</div>
	</div>

</form>
