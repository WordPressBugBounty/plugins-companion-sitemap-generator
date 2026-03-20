<?php

/**
 * HTML sitemap Functions
 *
 * This file contains functions regarding the HTML sitemap
 *
 *
 * @see     https:/wijzijnqreative.nl/url-to-be-added/
 * @version 4.6.0
 */

 // HTML sitemap page
 function csg_get_sitemap_page() {
	global $wpdb;
	$query 	= "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%wp:sitemap/block%' AND post_status = 'publish' LIMIT 1";
	$value 	= $wpdb->get_var( $query );
	return ! empty( $value ) ? $value : false;
 }

 // Get all post types for html sitemap
 function html_posttypes( $sort, $orderby, $numberofcolumns, $limit ) {

	$posts 			= '';
	$columns 		= esc_attr( $numberofcolumns );
	$post_types 	= array_diff( csg_get_all( "post_types" ), csg_get_excluded( "post_types" ) );

	foreach( $post_types  as $post_type ) {

		$csg_sitemap_args = array(
			'order' 			=> $sort,
			'post_type' 		=> $post_type,
			'posts_per_page' 	=> $limit,
			'post_status' 		=> 'publish',
			'post__not_in' 		=> csg_get_excluded( "post_ids" ),
			'orderby'			=> $orderby,
			'post_parent' 		=> 0,
		);

		$csg_sitemap_posts = get_posts( $csg_sitemap_args );

		if ( $csg_sitemap_posts ) {

			$get_post_type = get_post_type_object( $post_type );

			$posts .= "<div class='sitemap-column sitemap-columns-{$columns} sitemap-posttypes'><div class='html-sitemap-column'><h2>{$get_post_type->label}</h2><ul>";

			if( is_post_type_hierarchical( $post_type ) ) {
				$csg_sitemap_args['echo'] 		= false;
				$csg_sitemap_args['title_li'] 	= false;
				$posts .= wp_list_pages( $csg_sitemap_args );

			} else {
				foreach( $csg_sitemap_posts as $post ) {
					$id 		= $post->ID;
					$title 		= get_the_title( $id );
					$permalink 	= get_the_permalink( $id );
					$posts .= "<li class='{$post_type}_item {$post_type}-item-{$id}'><a href='{$permalink}'>{$title}</a></li>";
				}
				wp_reset_postdata();
			}
			$posts .= "</ul></div></div>";


		}

	}

	return $posts;

 }

 // Taxonomies
 function html_taxonomies( $sort, $orderby, $numberofcolumns ) {

	$return 		= '';
	$columns 		= esc_attr( $numberofcolumns );
	$taxonomies 	= array_diff( csg_get_all( "taxonomies" ), csg_get_excluded( "post_types" ) );

	if ( $taxonomies ) {

		foreach( $taxonomies as $taxonomie ) {

			$thisTaxonomie 	= get_taxonomy( $taxonomie );
			$terms 			= get_terms( array( 'taxonomy' => $taxonomie, 'orderby' => $orderby, 'order' => $sort, 'hide_empty' => true, 'exclude' => csg_get_excluded( "taxonomies" ) ) );

			if( !empty( $terms ) ) {

				$return .= "<div class='sitemap-column sitemap-columns-{$columns} sitemap-posttypes'><div class='html-sitemap-column'><h2>{$thisTaxonomie->label}</h2><ul>";

				foreach( $terms as $tax ) {
					$id 		= $tax->term_id;
					$title 		= $tax->name;
					$permalink 	= get_term_link( $id );
					$return 	.= "<li class='{$post_type}_item {$post_type}-item-{$id}'><a href='{$permalink}'>{$title}</a></li>";
				}

				$return .= "</ul></div></div>";


			}

		}

	}

	return $return;

 }

 // Aditional pages
 function html_additionalpages( $sort, $orderby, $numberofcolumns ) {

	$return 				= '';
	$columns 				= esc_attr( $numberofcolumns );
	$additionalpages 		= csg_get_additionalpages();

	if ( !empty( $additionalpages ) && $additionalpages[0] != '' ) {

		$additionalpages_label = __( 'Additional pages', 'companion-sitemap-generator' );
		$return .= "<div class='sitemap-column sitemap-columns-{$columns} sitemap-posttypes'><div class='html-sitemap-column'><h2>{$additionalpages_label}</h2><ul>";

		foreach( $additionalpages as $additionalpage ) {
			$page = sanitize_text_field( $additionalpage );
			$return .= "<li class='additional_item'><a href='{$page}'>{$page}</a></li>";
		}

		$return .= "</ul></div></div>";

	}

	return $return;

 }
