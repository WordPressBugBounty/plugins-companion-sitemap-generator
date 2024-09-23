<?php

// Submit
if( isset( $_POST['submit'] ) ) {

	check_admin_referer( 'csg_save_select' );

	global $wpdb;
	$table_name 		= $wpdb->prefix."csg_sitemap";
	$excludeposts 		= array();
	$exclude_counter 	= 0;

	if( !empty( $_POST['post'] ) ) {

		foreach ( $_POST['post'] as $key ) array_push( $excludeposts, sanitize_text_field( $key ) );
		$exclude_these 		= implode( ", ", $excludeposts );
		$exclude_counter 	= count( $excludeposts );
		$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET onoroff = %s WHERE name = 'exclude'", $exclude_these ) );

	} else {

		$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET onoroff = %s WHERE name = 'exclude'", '' ) );

	}

	csg_select_succes( $exclude_counter );

}

echo "<div id='message' class='info'>".__( 'Remove items from your sitemap by checking the checkbox. You can always add them back by unchecking it again.', 'companion-sitemap-generator' )."</div>";

// Show subtabs
echo "<ul class='subsubsub' style='margin: 10px 0;'>";
	foreach( csg_get_posttypes() as $key => $type ) {
		if( $type != 'post' ) echo " | ";
		$active = ( $type == 'post' ) ? 'current' : '';
		echo "<li><a data-table='table-".$type."' class='showtable table-".$type." ".$active."'>".get_post_type_object( $type )->label."</a></li>";
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

	foreach( csg_get_posttypes() as $postType ) { ?>

		<table class="wp-list-table widefat striped table-<?php echo $postType; ?> csg-table">

			<thead>
				<tr>
					<td  id='cb' class='manage-column column-cb check-column'><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox" /></td>
					<th scope="col" id='title' class='manage-column column-title column-primary'><?php _e('Title', 'companion-sitemap-generator'); ?></th>
					<th scope="col" id='permalink' class='manage-column'><?php _e('Permalink', 'companion-sitemap-generator'); ?></th>
					<?php if( csg_is_multilingual() ) {
						echo "<th scope='col' id='permalink' class='manage-column'>".__( 'Languages', 'companion-sitemap-generator' )."</th>";
					} ?>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php 

				// Create empty string
				$csg_sitemap_content = '';

				// Arguments for selecting pages
				$csg_sitemap_args = array(
					'sortby'			=> 'date',
					'order' 			=> 'desc',
					'post_type' 		=> $postType, 
					'posts_per_page' 	=> '-1',
					'post_status' 		=> 'publish'
				);

				// If is multilingual add language filter
				if( csg_is_multilingual() ) {
					$csg_sitemap_args['lang'] = csg_default_language();
				}

				$frontpageid = get_option('page_on_front');

				// The Query
				query_posts( $csg_sitemap_args );

				// The Loop
				if( have_posts() ) :
				while ( have_posts() ) : the_post();

					global $wpdb;
					$table_name = $wpdb->prefix . "sitemap-excludes";
					$checked 	= in_array( get_the_id(), csg_exclude() ) ? 'CHECKED' : ''; 
					$showmore 	= ( get_the_id() == $frontpageid ) ? '<span class="post-state">&dash; '.__( "Front page" ).'</span>' : '';

					echo '
					<tr id="post-'.get_the_id().'">
						<th scope="row" class="check-column">			
							<label class="screen-reader-text" for="cb-select-'.get_the_id().'">Select '. get_the_title() .'</label>
							<input id="cb-select-'.get_the_id().'" type="checkbox" name="post[]" value="'.get_the_id().'" '.$checked.'/>
						</th>
						<td class="title column-title column-primary page-title" data-colname="Title">
							<strong><a href="'. get_the_permalink() .'" target="_blank">'. get_the_title() .'</a> '.$showmore.'</strong>
						</td>
						<td class="permalink column-permalink column-secondar page-permalink" data-colname="Permalink">
							'. get_the_permalink() .'
						</td>';
						if( csg_is_multilingual() ) {
							echo '<td class="languages column-languages column-secondar page-languages" data-colname="Permalink">
							<ol>'; 
							foreach ( csg_languages() as $key => $lang ) {
								if( csg_post_translation_id( get_the_ID(), $lang ) != '' ) {
									echo '<li>'.$lang.'</li>';
								}
							}
							echo '</ol></td>';
						}
					echo '</tr>';

					$showmore = '';
					$checked = '';

				endwhile;

				else:
					
					echo '<tr class="no-items"><td class="colspanchange" colspan="3">';
					echo sprintf( esc_html__( 
						'Nothing found in %1$s.', 'companion-sitemap-generator' 
					), $postType );
					echo '</td></tr>';

				endif;

				// Reset Query
				wp_reset_query();

				?>
			</tbody>
		</table>

	<?php }

	wp_nonce_field( 'csg_save_select' );

	submit_button(); ?>
</form>