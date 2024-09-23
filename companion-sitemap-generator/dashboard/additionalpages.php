<?php

// Save settings
if( isset( $_POST['submit'] ) ) {

	check_admin_referer( 'csg_additionalpages' );

	// Save frequency
	global $wpdb;
	$table_name = $wpdb->prefix . "csg_sitemap"; 

	if( isset( $_POST["additionalpages"] ) ) $additionalpages = sanitize_text_field( $_POST['additionalpages'] ); else $additionalpages = '';
	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'additionalpages'", $additionalpages ) );

	echo '<div id="message" class="updated"><p>'.__( 'Settings saved', 'companion-sitemap-generator' ).'.</p></div>';

}

?>

<p><?php _e( 'Add pages to the sitemap in addition to your normal WordPress ones. Just paste the full urls in the textarea.', 'companion-sitemap-generator' ); ?></p>

<form method="POST">
	
	<textarea name="additionalpages" style="width: 100%; height: 400px;"><?php echo csg_get_additionalpages__textarea(); ?></textarea>

	<?php wp_nonce_field( 'csg_additionalpages' ); ?>
	<?php submit_button(); ?>

</form>