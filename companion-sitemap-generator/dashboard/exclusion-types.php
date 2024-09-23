<?php

// Submit
if( isset( $_POST['submit'] ) ) {

	check_admin_referer( 'csg_save_types' );

	global $wpdb;
	$table_name 		= $wpdb->prefix."csg_sitemap";
	$excludeposts 		= array();
	$exclude_counter 	= 0;

	if( !empty( $_POST['post'] ) ) {

		foreach ( $_POST['post'] as $key ) array_push( $excludeposts, sanitize_text_field( $key ) );
		$exclude_these 		= implode( ", ", $excludeposts );
		$exclude_counter 	= count( $excludeposts );
		$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET onoroff = %s WHERE name = 'ctam'", $exclude_these ) );

	} else {

		$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET onoroff = %s WHERE name = 'ctam'", '' ) );

	}

	csg_select_succes( $exclude_counter );

}

echo "<div id='message' class='info'>".__( 'Remove items from your sitemap by checking the checkbox. You can always add them back by unchecking it again.', 'companion-sitemap-generator' )."</div>";

// Show subtabs
echo "<ul class='subsubsub' style='margin: 10px 0;'>";
	foreach( csg_get_taxonomies() as $key => $tax ) {
		if( $key > 0 ) echo " | ";
		$active = ( $key == 0 ) ? 'current' : '';
		echo "<li><a data-table='table-".$tax."' class='showtable table-".$tax." ".$active."'>".get_taxonomy( $tax )->label."</a></li>";
	}
echo "</ul>

<br class='clear'>";

?>

<form method="POST">

	<script type="text/javascript">
		jQuery( '.showtable' ).click(function() {
			jQuery( '.showtable' ).removeClass( 'current' );
			jQuery( this ).addClass( 'current' );
			var thisClass = jQuery(this).attr( 'data-table' );
			jQuery( '.wp-list-table' ).hide();
			jQuery( '.'+thisClass ).show();
		});
	</script>

	<?php 

	foreach( csg_get_taxonomies() as $key => $type ) { ?>

		<table class="wp-list-table widefat striped table-<?php echo $type; ?> csg-table">

			<thead>
				<tr>
					<td  id='cb' class='manage-column column-cb check-column'><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox" /></td>
					<th scope="col" id='title' class='manage-column column-title column-primary'><?php _e( 'Title', 'companion-sitemap-generator' ); ?></th>
					<th scope="col" id='permalink' class='manage-column'><?php _e( 'Permalink', 'companion-sitemap-generator' ); ?></th>
					<?php if( csg_is_multilingual() ) {
						echo "<th scope='col' id='permalink' class='manage-column'>".__( 'Languages', 'companion-sitemap-generator' )."</th>";
					} ?>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php 

				$taxonomie = $type;
					
				// Get all terms by taxonomy
				global $wp_version;
				$terms = get_terms( array( 'taxonomy' => $taxonomie ) );

				if( !empty( $terms ) ) {

					foreach( $terms as $key => $term ) {

						$tid 		= $term->term_id;
						$catName 	= $term->name;
						$catLink 	= get_term_link( $tid );
						$checked 	= in_array( $tid, csg_exclude_ctam() ) ? 'CHECKED' : '';

						echo '<tr id="'.$tid.'">
							<th scope="row" class="check-column">			
								<label class="screen-reader-text" for="cb-select-'.$tid.'">Select '.$catName.'</label>
								<input id="cb-select-'.$tid.'" type="checkbox" name="post[]" value="'.$tid.'" '.$checked.'/>
							</th>
							<td class="title column-title column-primary page-title" data-colname="Title">
								<strong><a href="'.$catLink.'" target="_blank">'.$catName.'</a></strong>
							</td>
							<td class="permalink column-permalink column-secondary page-permalink" data-colname="Permalink">
								'.$catLink.'
							</td>';
							if( csg_is_multilingual() ) {
								echo '<td class="languages column-languages column-secondar page-languages" data-colname="Permalink">
								'.csg_get_term_language( $tid ).'
								</td>';
							}
						echo '</tr>';

					}

				} else {
					
					echo '<tr class="no-items"><td class="colspanchange" colspan="3">';
					echo sprintf( esc_html__( 
						'Nothing found in %1$s.', 'companion-sitemap-generator' 
					), $taxonomie );
					echo '</td></tr>';

				}
			
				?>
			</tbody>
		</table>

	<?php }

	wp_nonce_field( 'csg_save_types' );

	submit_button(); ?>
	
</form>