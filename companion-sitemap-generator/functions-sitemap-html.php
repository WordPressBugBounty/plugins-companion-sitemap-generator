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

// Output column
function csg_output_html_column( string $label, array $items ): string {

	// Only show if items are set
	if( empty( $items ) ) {
		return "";
	}

	// Prepare
	$heading 	= esc_html( $label );
	$coltype 	= sanitize_key( $label );
	$show_items = implode( "", $items );

	return sprintf(
		'<section class="sitemap-column sitemap-column--%1$s" aria-labelledby="sitemap-heading--%1$s">
			<h3 id="sitemap-heading--%1$s">%2$s</h3>
			<ul class="sitemap-page-list">
				%3$s
			</ul>
		</section>',
		$coltype,
		$heading,
		$show_items
	);
}

// Prepare the URLs
function csg_output_html_line( int $id, string $title, string $permalink ): string {

	// Basic checks
	if( $id === 0 ) {
		return "";
	}

	// Escaping
	$id 		= esc_attr( $id );
	$title 		= esc_html( $title );
	$permalink 	= esc_url( $permalink );

	// Output
	return sprintf(
		"<li class='sitemap-item sitemap-item--%d'>
			<a href='%s'>%s</a>
		</li>",
		$id,
		$permalink,
	 	$title
	);

}

 // Get all post types for html sitemap
 function html_posttypes( string $sort, string $orderby, int $limit ): string {

	$html_posts 	= [];
	$sort 			= esc_attr( $sort );
	$orderby 		= esc_attr( $orderby );
	$limit 			= esc_attr( $limit );
	$get_objects 	= array_diff( csg_get_all( "post_types" ), csg_get_excluded( "post_types" ) );
	$excluded 		= csg_get_excluded( "post_ids" );

	if( empty( $get_objects ) ) {
		return "";
	}

	foreach( $get_objects  as $object ) {

		$get_args = [
			'order' 			=> $sort,
			'post_type' 		=> $object,
			'posts_per_page' 	=> $limit,
			'post_status' 		=> 'publish',
			'post__not_in' 		=> $excluded,
			'orderby'			=> $orderby,
			'post_parent' 		=> 0,
		];

		$the_objects = get_posts( $get_args );
		if ( !$the_objects ) {
			continue;
		}

		$col_post_type 	= get_post_type_object( $object );
		$col_type_label	= esc_html( $col_post_type->label ?? __("No title") );
		$col_post_items = [];

		foreach( $the_objects as $single_object ) {
			$id 				= $single_object->ID ?? 0;
			$title 				= get_the_title( $id ) ?? "";
			$permalink 			= get_the_permalink( $id ) ?? "";
			$col_post_items[] 	= csg_output_html_line( $id, $title, $permalink );
		}

		wp_reset_postdata();

		$html_posts[] = csg_output_html_column( $col_type_label, $col_post_items );

	}

	// If no posts
	if( empty( $html_posts ) ) {
		return "";
	}

	// To string
	return implode( '', $html_posts );

 }

 // Taxonomies
 function html_taxonomies( string $sort, string $orderby, int $limit ): string {

 	$html_posts 	= [];
	$sort 			= esc_attr( $sort );
	$orderby 		= esc_attr( $orderby );
	$limit 			= esc_attr( $limit );
	$get_objects 	= array_diff( csg_get_all( "taxonomies" ), csg_get_excluded( "post_types" ) );
	$excluded 		= csg_get_excluded( "taxonomies" );

	if( empty( $get_objects ) ) {
		return "";
	}

	foreach( $get_objects as $object ) {

		$get_taxonomy = get_taxonomy( $object );
		if( !$get_taxonomy ) {
			continue;
		}

		$get_args = [
			'taxonomy' 			=> $get_taxonomy->name,
			'order' 			=> $sort,
			'exclude' 			=> $excluded,
			'orderby'			=> $orderby,
			'hide_empty' 		=> true,
		];

		$the_objects = get_terms( $get_args );
		if ( !$the_objects ) {
			continue;
		}

		$col_type_label	= esc_html( $get_taxonomy->label ?? __("No title") );
		$col_post_items = [];

		foreach( $the_objects as $single_object ) {
			$id 				= $single_object->term_id ?? 0;
			$title 				= $single_object->name ?? "";
			$permalink 			= get_term_link( $id ) ?? "";
			$col_post_items[] 	= csg_output_html_line( $id, $title, $permalink );
		}

		wp_reset_postdata();

		$html_posts[] = csg_output_html_column( $col_type_label, $col_post_items );

	}

	// If no posts
	if( empty( $html_posts ) ) {
		return "";
	}

	// To string
	return implode( '', $html_posts );

 }

 // Aditional pages
 function html_additionalpages( string $sort, string $orderby, int $limit ): string {

	// $return 				= '';
	// $columns 				= esc_attr( 0 );
	// $additionalpages 		= csg_get_additionalpages();

	// if ( !empty( $additionalpages ) && $additionalpages[0] != '' ) {

	// 	$additionalpages_label = __( 'Additional pages', 'companion-sitemap-generator' );
	// 	$return .= "<div class='sitemap-column sitemap-columns-{$columns} sitemap-posttypes'><div class='html-sitemap-column'><h2>{$additionalpages_label}</h2><ul>";

	// 	foreach( $additionalpages as $additionalpage ) {
	// 		$page = sanitize_text_field( $additionalpage );
	// 		$return .= "<li class='additional_item'><a href='{$page}'>{$page}</a></li>";
	// 	}

	// 	$return .= "</ul></div></div>";

	// }

	// return $return;

	return "";

 }
