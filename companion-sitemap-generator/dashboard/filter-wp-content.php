<?php

// Get the correct content
$posttype_array 	= csg_get_all( "post_types" );
$taxonomies_array 	= csg_get_all( "taxonomies" );
$taxonomy_icons 	= array( 'category' => 'dashicons-category', 'post_tag' => 'dashicons-tag', 'post_format' => 'dashicons-format-aside' );
$fallback_icon 		= "dashicons-admin-plugins";

$filter_groups 	= array(
	'post_types' => array(
		'label' 	=> esc_html__( 'Post Types', 'companion-sitemap-generator' ),
		'objects' 	=> $posttype_array
	),
	'taxonomies' => array(
		'label' 	=> esc_html__( 'Taxonomies', 'companion-sitemap-generator' ),
		'objects' 	=> $taxonomies_array
	),
);

// Submit
if( isset( $_POST['submit'] ) ) {

	check_admin_referer( 'csg_save_select' );

	global $wpdb;
	$table_name = sprintf( "%s%s", $wpdb->prefix, CSG_DB_TABLE );

	// Single posts
	$exclude_posts 		= '';
	$count_posts 		= 0;

	if( ! empty( $_POST['post'] ) ) {
		$POST_post 		= array_filter( $_POST['post'] );
		$POST_post 		= rest_sanitize_array( $POST_post );
		$exclude_posts 	= ! empty( $POST_post ) ? implode( ', ', $POST_post ) : '';
		$count_posts 	= ! empty( $POST_post ) ? count( $POST_post ) : 0;
	}

	$posts_query = $wpdb->query( $wpdb->prepare(
		"UPDATE %i SET onoroff = '%s' WHERE name = 'exclude'",
		$table_name,
		$exclude_posts
	) );

	if( ! $posts_query && $wpdb->last_error ) {
		wp_admin_notice( esc_html( $wpdb->last_error ), [ "type" => "error" ] );
	}

	// Post types
	$exclude_post_types 	= '';
	$count_post_types 		= 0;

	if( ! empty( $_POST['post_type'] ) ) {
		$POST_post_type 		= array_filter( $_POST['post_type'] );
		$filtered_post_type 	= rest_sanitize_array( $POST_post_type );
		$exclude_post_types 	= ! empty( $filtered_post_type ) ? implode( ', ', $filtered_post_type ) : '';
		$count_post_types 		= ! empty( $filtered_post_type ) ? count( $filtered_post_type ) : 0;
	}

	// Taxonomies
	$exclude_taxonomies 	= '';
	$count_taxonomies 		= 0;

	if( ! empty( $_POST['taxonomy'] ) ) {
		$POST_taxonomy 		= array_filter( $_POST['taxonomy'] );
		$filtered_taxonomy 	= rest_sanitize_array( $POST_taxonomy );
		$exclude_taxonomies = ! empty( $filtered_taxonomy ) ? implode( ', ', $filtered_taxonomy ) : '';
		$count_taxonomies 	= ! empty( $filtered_taxonomy ) ? count( $filtered_taxonomy ) : 0;
	}

	// Merge taxonomies and post types
	$posttypes_exc_array = [];
	if( ! empty( $exclude_post_types ) ) $posttypes_exc_array[] = $exclude_post_types;
	if( ! empty( $exclude_taxonomies ) ) $posttypes_exc_array[] = $exclude_taxonomies;
	$posttypes_exclusion = ! empty( $posttypes_exc_array ) ? implode( ', ', $posttypes_exc_array ) : '';

	$post_types_and_tax_query = $wpdb->query( $wpdb->prepare(
		"UPDATE %i SET onoroff = '%s' WHERE name = 'posttypes'",
		$table_name,
		$posttypes_exclusion
	) );

	if( ! $post_types_and_tax_query && $wpdb->last_error ) {
		wp_admin_notice( esc_html( $wpdb->last_error ), [ "type" => "error" ] );
	}

	// Terms
	$exclude_terms 		= '';
	$count_terms 		= 0;

	if( ! empty( $_POST['term'] ) ) {
		$POST_terms 	= array_filter( $_POST['term'] );
		$POST_terms 	= rest_sanitize_array( $POST_terms );
		$exclude_terms	= ! empty( $POST_terms ) ? implode( ', ', $POST_terms ) : '';
		$count_terms 	= ! empty( $POST_terms ) ? count( $POST_terms ) : 0;
	}

	$terms_query = $wpdb->query( $wpdb->prepare(
		"UPDATE %i SET onoroff = '%s' WHERE name = 'ctam'",
		$table_name,
		$exclude_terms
	) );

	if( ! $terms_query && $wpdb->last_error ) {
		wp_admin_notice( esc_html( $wpdb->last_error ), [ "type" => "error" ] );
	}

	// Total
	$notice_count 	= ( (int) $count_posts + (int) $count_post_types + (int) $count_terms + (int) $count_taxonomies );
	$notice_title 	= sprintf( esc_html__( '%s items will be hidden from your sitemap', 'companion-sitemap-generator' ), $notice_count );

	$notice_array 	= [];
	if( $count_posts > 0 ) 			$notice_array[] = "{$count_posts} posts";
	if( $count_post_types > 0 ) 	$notice_array[] = "{$count_post_types} post types";
	if( $count_terms > 0 ) 			$notice_array[] = "{$count_terms} terms";
	if( $count_taxonomies > 0 ) 	$notice_array[] = "{$count_taxonomies} taxonomies";

	$notice_descr 	= ! empty( $notice_array ) ? "<br /><mark><small>".implode( ", ", $notice_array )."</small></mark>" : "";
	wp_admin_notice( sprintf( "%s%s", $notice_title, $notice_descr ), [ "type" => "success" ] );

}

// The table
echo "<form method='POST' action=''>";

	foreach( $filter_groups as $group_id => $group_info ) {

		echo "<div style='height: 1rem;' aria-hidden='true'></div>

		<section id='{$group_id}'>

			<h2>{$group_info['label']}</h2>

			<div class='accordion-group'>";

			foreach( $group_info['objects'] as $object ) {

				// Starting vars
				$object_id 			= sanitize_key( $object );

				// Fetch the correct info
				if( $group_id === "post_types" ) {

					$get_object 		= get_post_type_object( $object );
					$object_icon 		= isset( $get_object->menu_icon ) ? esc_html( $get_object->menu_icon ) : esc_html( $fallback_icon );
					$object_compare 	= csg_get_excluded( "post_ids" );

					$group_form_arr 	= "post_type[]";

					// Get all posts by post type
					$arguments 			= array( 'sortby' => 'date', 'order' => 'desc', 'post_type' => $object_id, 'posts_per_page' => '-1', 'post_status' => 'publish' );
					if( csg_is_multilingual() ) $arguments['lang'] = csg_default_language(); // If is multilingual add language filter
					$get_objects 		= get_posts( $arguments );

				} elseif( $group_id === "taxonomies" ) {

					$get_object 		= get_taxonomy( $object );
					$object_icon 		= isset( $taxonomy_icons[$object] ) ? esc_html( $taxonomy_icons[$object] ) : esc_html( $fallback_icon );
					$object_compare 	= csg_get_excluded( "taxonomies" );

					$group_form_arr 	= "taxonomy[]";

					// Get all posts by tax
					$arguments 			= array( 'taxonomy' => $object_id );
					$get_objects		= get_terms( $arguments );

				}

				// Only get the hidden posts (when the array is empty get_posts returns all posts.. so that doesn't work then)
				if( ! empty( $object_compare ) ) {
					$arguments['include'] 	= $object_compare;
					$get_hidden_posts 		= get_posts( $arguments );
					$hidden_post_count 		= count( $get_hidden_posts );
				} else {
					$hidden_post_count 		= 0;
				}

				$object_label 	= esc_html( $get_object->label );
				$object_slug 	= sanitize_key( $get_object->name );

				// $single_name 	= isset( $get_object->labels->singlular_name ) ? strtolower( esc_html( $get_object->labels->singlular_name ) ) : strtolower( esc_html( $get_object->labels->name_admin_bar ) );
				$single_name 	= esc_html( $get_object->name );
				$all_items 		= esc_html( $get_object->labels->all_items );
				$not_found 		= esc_html( $get_object->labels->not_found );
				$items_list 	= esc_html( $get_object->labels->items_list );

				// If the entire post type is set to be excluded from the sitemap
				$global_exclude = in_array( $object, csg_get_excluded( "post_types" ) ) ? true : false;

				// For the badge
				$total_post_count 	= count( $get_objects );
				$badge_string	 	= sprintf( esc_html__( '%s hidden', 'companion-sitemap-generator' ), (int) $hidden_post_count );
				$badge_color 		= "green";

				// No items found
				if( $total_post_count === 0 ) {
					$badge_color 	= "blue";
					$badge_string 	= $not_found;
				}

				// All items excluded
				else if( $global_exclude OR $hidden_post_count === $total_post_count ) {
					$badge_color 	= "red";
					$badge_string 	= sprintf( esc_html__( '%s hidden', 'companion-sitemap-generator' ), $all_items );
				}

				// Translations and such
				$description 	= sprintf( esc_html__( 'You can hide %s from the sitemap or select individual %s from the table below', 'companion-sitemap-generator' ), strtolower( $all_items ), strtolower( $object_label ) );
				$show_all 		= sprintf( esc_html__( 'Show %s in sitemap', 'companion-sitemap-generator' ), strtolower( $all_items ) );
				$hide_all 		= sprintf( esc_html__( 'Hide %s from sitemap', 'companion-sitemap-generator' ), strtolower( $all_items ) );
				$hidden_counter = sprintf( esc_html__( '%s hidden', 'companion-sitemap-generator' ), "<strong>{$hidden_post_count}</strong> / {$total_post_count}" );

				// Output the accordion
				echo "<details id='{$group_id}-accordion' class='sitemap-accordion' name='{$group_id}-exclusion-accordion' data-group='{$group_id}' data-object='{$object_id}'>

					<summary>
						<span class='dashicons {$object_icon}' aria-hidden='true'></span>
						<span class='title'>{$object_label} <mark style='margin-left: 0.5em;'><small>[{$object_slug}]</small></mark></span>
						<span class='badge {$badge_color}'>{$badge_string}</span>
						<span class='icon'></span>
					</summary>

					<div class='panel'>

						<header style='display: grid; grid-template-columns: 1fr 1fr; align-items: center'>
							<div class='left'>
								<label for='{$object_id}-setting'><strong>{$all_items}</strong></label>
								<p>{$description}</p>
							</div>
							<div class='right' style='text-align: right;'>
								<p><select name='{$group_form_arr}' id='{$object_id}-setting' class='global_toggle' data-objectlist='{$object_id}' onchange='csg_table_state( this )'>
									<option value=''>{$show_all}</option>
									<option value='{$object_id}' ".( $global_exclude ? 'SELECTED' : '' ).">{$hide_all}</option>
								</select></p>
							</div>
						</header>";

						// No objects found
						if( ! $get_objects ) {
							echo "<div class='notice notice-warning'><p>{$not_found}</p></div>";

						// Output table
						} else {

							echo "<table id='{$object_id}-list' class='widefat striped ".( $global_exclude ? 'inactive' : '' )."'>
								<thead class='accordion-panel-header'>
									<tr>
										<td><strong>{$items_list}</strong></td>
										<td style='text-align: right;'><mark>{$hidden_counter}</mark></td>
									</tr>
								</thead>
								<tbody>";

									foreach( $get_objects as $key => $post ) {

										// Get the correct objects
										if( $group_id === "post_types" ) {
											$post_id		= sanitize_key( $post->ID );
											$post_title 	= esc_attr( $post->post_title );
											$post_link 		= get_the_permalink( $post_id );
											$post_form_arr 	= "post[]";

										} elseif( $group_id === "taxonomies" ) {
											$post_id		= sanitize_key( $post->term_id );
											$post_title 	= esc_attr( $post->name );
											$post_link 		= get_term_link( $post->slug, $object_id );
											$post_form_arr 	= "term[]";
										}

										// Some formatting and stuff
										$object_is_hidden 	= in_array( $post_id, $object_compare ) ? true : false;
										$object_selected 	= $object_is_hidden ? "selected='selected'" : "";
										$table_row_class 	= $object_is_hidden ? "inactive" : "active";
										$show_single 		= sprintf( esc_html__( 'Show %s in sitemap', 'companion-sitemap-generator' ), strtolower( $single_name ) );
										$hide_single 		= sprintf( esc_html__( 'Hide %s from sitemap', 'companion-sitemap-generator' ), strtolower( $single_name ) );
										$view_single 		= sprintf( esc_html__( 'View %s', 'companion-sitemap-generator' ), strtolower( $single_name ) );

										$object_url 		= esc_url( $post_link );
										$object_slug 		= str_replace( get_site_url(), '', $object_url );

										// Multilingual support
										$post_languages = array();
										$post_lang_cell = "";
										if( csg_is_multilingual() ) {
											foreach ( csg_languages() as $key => $lang ) {
												if( csg_post_translation_id( get_the_ID(), $lang ) !== '' ) {
													$post_languages[] = $lang;
												}
											}
											if( ! empty( $post_languages ) ) {
												$post_lang_cell = "<td><strong>".esc_html__( 'Languages', 'companion-sitemap-generator' )."</strong>:<ol><li>".implode( "</li><li>", $post_languages )."</li></ol></td>";
											}
										}

										// Output the table row
										echo "<tr id='{$post_id}-list' class='{$table_row_class}'>

											<td class='post-title' style='vertical-align: middle;'>
												<strong><a href='{$object_url}' target='_blank' title='{$view_single}'>{$post_title}</a></strong><br />
												<mark class='post-link'>{$object_slug}</mark>
											</td>

											<td class='post-settings' style='vertical-align: middle; text-align: right;'>
												<label for='{$post_id}-status' class='screen-reader-text'>{$post_title}</label>

												<select name='{$post_form_arr}' id='{$post_id}-status' data-objectlist='{$post_id}' onchange='csg_table_state( this )'>
													<option value=''>{$show_single}</option>
													<option value='{$post_id}' {$object_selected}>{$hide_single}</option>
												</select>

											</td>
											{$post_lang_cell}
										</tr>";

									}

								echo "</tbody>
							</table>";

						}

						echo "<footer class='accordion-panel-footer'>
							".get_submit_button( '', 'primary', 'submit', false )."
							<a href='#{$group_id}-accordion' class='button'>Back to top &#10548;</a>
						</footer>

					</div>
				</details>";

				// echo '<details id="'.$object_id.'-exclusion-accordion" class="sitemap-accordion" name="exclusion-accordion">

				// 	<div class="panel">

				// 		<script>jQuery( "#'.$object_id.'-setting" ).change(function() { if( jQuery(this).val() == "'.$object_id.'" ) { jQuery( "#'.$object_id.'-table" ).addClass( "table_inactive" ); } else { jQuery( "#'.$object_id.'-table" ).removeClass( "table_inactive" ); } });</script>

				// 		<h3 style="margin-top: 2rem; display: flex; justify-content: space-between;">'.$items_list.' <span style="opacity: 0.6; font-weight: normal;">'.sprintf( esc_html__( '%s hidden', 'companion-sitemap-generator' ), "<strong>{$hidden_post_count}</strong> / {$total_post_count}" ).'</span></h3>
				// 		<table id="'.$object_id.'-table" class="widefat plugins">
				// 			<tbody id="the-list">';


				// 				foreach( $get_posts as $key => $post ) {

				// 					if( $group_id === "post_types" ) {
				// 						$post_id		= sanitize_key( $post->ID );
				// 						$post_title 	= esc_attr( $post->post_title );
				// 						$post_link 		= str_replace( get_site_url(), '', get_the_permalink( $post_id ) );
				// 						$the_array 		= "post[]";

				// 					} elseif( $group_id === "taxonomies" ) {
				// 						$post_id		= sanitize_key( $post->term_id );
				// 						$post_title 	= esc_attr( $post->name );
				// 						$post_link 		= str_replace( get_site_url(), '', get_term_link( $post->slug, $object_id ) );
				// 						$the_array 		= "term[]";
				// 					}

				// 					$post_languages = array();
				// 					$post_lang_cell = "";

				// 					// Bunch of variables to help distinguish between excluded and included
				// 					$post_excluded 	= in_array( $post_id, $compare ) ? true : false;
				// 					if( $post_excluded ) {
				// 						$excluded 		= "SELECTED";
				// 						$status 		= "row_inactive";
				// 					} else {
				// 						$excluded 		= "";
				// 						$status 		= "";
				// 					}

				// 					// Multilingual support
				// 					if( csg_is_multilingual() ) {
				// 						foreach ( csg_languages() as $key => $lang ) {
				// 							if( csg_post_translation_id( get_the_ID(), $lang ) !== '' ) {
				// 								$post_languages[] = $lang;
				// 							}
				// 						}
				// 						if( ! is_empty( $post_languages ) ) {
				// 							$post_lang_cell = "<td><strong>".esc_html__( 'Languages', 'companion-sitemap-generator' )."</strong>:<ol><li>".implode( "</li><li>", $post_languages )."</li></ol></td>";
				// 						}
				// 					}

				// 					echo "<tr style='font-weight: normal;' class='{$status}'>

				// 						<td class='post-title' style='vertical-align: middle;'>
				// 							<strong>{$post_title}</strong><br />
				// 							<span class='post-link'>{$post_link}</span>
				// 						</td>

				// 						<td class='post-settings' style='vertical-align: middle; text-align: right;'>
				// 							<label for='select_{$post_id}' class='screen-reader-text'>{$post_title}</label>

				// 							<select name='{$the_array}' id='select_{$post_id}'>
				// 								<option value=''>".sprintf( esc_html__( 'Include %s in sitemap', 'companion-sitemap-generator' ), $single_name )."</option>
				// 								<option value='{$post_id}' {$excluded}>".sprintf( esc_html__( 'Exclude %s from sitemap', 'companion-sitemap-generator' ), $single_name )."</option>
				// 							</select>

				// 						</td>
				// 						{$post_lang_cell}
				// 					</tr>";
				// 				}

				// 			echo '</tbody>

				// 		</table>
				// 	</div>

				// 	<div class="accordion-panel-footer">';
				// 		submit_button();
				// 		echo "<p><a href='#{$object_id}-exclusion-accordion'>Back to top &#10548;</a></p>
				// 	</div>

				// </details>";

			}

			echo "</div>
		</section>";

	}

	wp_nonce_field( 'csg_save_select' );

echo "</form>";
