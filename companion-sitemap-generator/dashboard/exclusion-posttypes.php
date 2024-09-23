<?php

// Post types and Taxonomies
$post_types 	= csg_get_posttypes(); 
$taxonomies 	= csg_get_taxonomies();

// Submit
if( isset( $_POST['submit'] ) ) {

	check_admin_referer( 'csg_save_posttypes' );

	global $wpdb;
	
	$table_name 			= $wpdb->prefix . "csg_sitemap";
	$excludes_post_types 	= array();

	// For all post types
	foreach ( $post_types  as $post_type ) {
		if( isset( $_POST['exclude_'.$post_type] ) == '' ) {
			array_push( $excludes_post_types, sanitize_text_field( $post_type ) );
		}
	}

	// Media
	if( isset( $_POST['exclude_media'] ) == '' ) {
		array_push( $excludes_post_types, 'media' );
	}

	// Loop trough all taxonomies
	foreach( $taxonomies as $taxonomie ) {

		// Get information of current one
		$thisTaxonomie = get_taxonomy( $taxonomie );

		if( isset( $_POST['exclude_'.$thisTaxonomie->name] ) == '' ) {
			array_push( $excludes_post_types, sanitize_text_field( $thisTaxonomie->name ) );
		}

	}

	$exclude_these 		= implode( ", ", $excludes_post_types );
	$exclude_counter 	= count( $excludes_post_types );

	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'posttypes'", $exclude_these ) );

	csg_select_succes( $exclude_counter );
}

echo "<form method='POST'>

	<div id='message' class='info'>".__( 'Remove post types from your sitemap by unchecking the checkbox. You can always add them back by checking it again.' )."</div>

	<table class='widefat striped'>

		<thead>
			<tr>
				<td id='cb' class='manage-column column-cb check-column'><label class='screen-reader-text' for='cb-select-all-1'>Select All</label><input id='cb-select-all-1' type='checkbox' /></td>
				<th colspan='2' scope='col' id='title' class='manage-column column-title column-primary'>".__( 'Title', 'companion-sitemap-generator' )."</th>
			</tr>
		</thead>

		<tbody id='the-list'>";

			// Add posttypes
			foreach ( $post_types  as $post_type ) {

				$post_typeO 		= get_post_type_object( $post_type ); 
				$post_type_name 	= $post_typeO->label;
				$checked 			= !in_array( $post_type, csg_exclude_posttypes() ) ? 'CHECKED' : '';

				echo "<tr>
					<th scope='row' class='check-column'><input id='exclude_{$post_type}' name='exclude_{$post_type}' type='checkbox' {$checked} ></th>
					<td scope='row' colspan='2'><strong>{$post_type_name}</strong></td>
				</tr>";
			}

			// Images
			$checked = !in_array( 'media', csg_exclude_posttypes() ) ? 'CHECKED' : '';
			echo "<tr>
				<th scope='row' class='check-column'><input id='exclude_media' name='exclude_media' type='checkbox' {$checked} ></th>
				<td scope='row' colspan='2'><strong>".__( 'Media' )."</strong></td>
			</tr>";

			// If there are any taxonomies
			if ( $taxonomies ) {

				// Loop trough all
				foreach( $taxonomies as $taxonomie ) {

					// Get information of current one
					$this_taxonomy 		= get_taxonomy( $taxonomie );
					$taxonomie_label 	= $this_taxonomy->label;
					$taxonomie_name 	= $this_taxonomy->name;
					$checked 			= !in_array( $this_taxonomy->name, csg_exclude_posttypes() ) ? 'CHECKED' : '';

					echo "<tr>
						<th scope='row' class='check-column'><input id='exclude_{$taxonomie_name}' name='exclude_{$taxonomie_name}' type='checkbox' {$checked} ></th>
						<td scope='row'><strong>{$taxonomie_label}</strong></td>
						<td scope='row'><span style='opacity: .6;'>{$taxonomie_name}</span></td>
					</tr>";

				} 
			} 

	echo "</tbody>
	</table>";

	wp_nonce_field( 'csg_save_posttypes' );

	submit_button();

echo "</form>";