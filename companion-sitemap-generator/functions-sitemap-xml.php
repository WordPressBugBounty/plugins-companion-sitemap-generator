<?php

/**
 * XML sitemap Functions
 *
 * This file contains functions regarding the XML sitemap
 *
 *
 * @see     https:/wijzijnqreative.nl/url-to-be-added/
 * @version 4.6.0
 */

 // Get the path to the xml file
 function csg_get_sitemap() {
	$sitemap_file = get_home_path() . csg_get_file_name( 'sitemap.xml' );
	return file_exists( $sitemap_file  )
		? esc_url( $sitemap_file )
		: false;
 }

 // Basically a wrapper function, this one is used by the scheduled event
 function csg_update_xml_sitemap() {
	csg_write_file( 'sitemap.xml', csg_output_xml() );
 }


 // Get the supported search engines
 function csg_get_supported_engines(): array {
	return [
		'Google' 	=> 'https://www.google.com/ping?sitemap=',
		'Bing' 		=> 'http://www.bing.com/ping?sitemap=',
		'Yandex' 	=> 'http://webmaster.yandex.com/site/map.xml?host=',
	];
 }

 // Ping search engines
 function csg_ping_engines() {
	$get_engines = csg_get_supported_engines();
	foreach( $get_engines as $engine => $ping_url ) {

		$engine_key = strtolower( $engine );
		$option_key = "ping_{$engine_key}";

		if( csg_option_enabled( $option_key ) ) {

			$ping_url 	= esc_url( $ping_url );
			$file_url 	= esc_url( csg_get_file_url( 'sitemap.xml' ) );
			$url 		= $ping_url . $file_url;

			$ch 		= curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$result 	= curl_exec( $ch );
			curl_close( $ch );
		}

	}
 }

 // Get the available frequency options
 function csg_frequency_options(): array {
	return [
		'hide' 			=>  esc_html__( 'Hide frequency', 'companion-sitemap-generator' ),
		'always' 		=>  esc_html__( 'Always', 'companion-sitemap-generator' ),
		'hourly' 		=>  esc_html__( 'Hourly', 'companion-sitemap-generator' ),
		'daily' 		=>  esc_html__( 'Daily', 'companion-sitemap-generator' ),
		'weekly' 		=>  esc_html__( 'Weekly', 'companion-sitemap-generator' ),
		'monthly' 		=>  esc_html__( 'Monthly', 'companion-sitemap-generator' ),
		'yearly' 		=>  esc_html__( 'Yearly', 'companion-sitemap-generator' ),
		'never' 		=>  esc_html__( 'Never', 'companion-sitemap-generator' ),
	];
 }

 // XML styling
 function csg_xml_default_styling(): string {
	return plugin_dir_url( __FILE__ ) . 'sitemap.xsl';
 }
 function csg_get_xml_stylesheet( bool $has_fallback = true ): string {
	$fallback = $has_fallback ? csg_xml_default_styling() : '';
	$stylesheet = csg_get_option( 'sitemap_stylesheet' ) ? csg_get_option( 'sitemap_stylesheet' ) : $fallback;
	return esc_url( $stylesheet );
 }

 // Output the XML
 function csg_output_xml() {

	// Base variables
	$csg_sitemap_content 	= [];
	$get_frequency 			= esc_attr( csg_get_option( 'frequency' ) );
	$get_images 			= in_array( 'attachment', csg_get_excluded( "post_types" ) ) ? false : true;
	$xml_insert_stylesheet 	= csg_get_option( 'use_sitemap_stylesheet' ) ? sprintf( "<?xml-stylesheet type='text/xsl' href='%s' ?>", csg_get_xml_stylesheet( true ) ) : "";

	// Start the XML output
	$csg_sitemap_content[] 	= "<?xml version='1.0' encoding='UTF-8' ?>{$xml_insert_stylesheet}";
	$csg_sitemap_content[] 	= "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xhtml='http://www.w3.org/1999/xhtml' xmlns:image='http://www.google.com/schemas/sitemap-image/1.1'>";

	// Add posts
	$posts 	= get_posts([
		'order' 			=> 'asc',
		'orderby' 			=> 'name',
		'posts_per_page' 	=> 5000,
		'post_status' 		=> 'publish',
		'post__not_in' 		=> csg_get_excluded( "post_ids" ),
		'post_type' 		=> csg_get_included( "post_types" ),
	]);

	if( $posts ) {
		foreach( $posts as $post ) {
			$csg_sitemap_content[] 	= csg_output_xml_line( $post->ID, "post", $get_frequency, $get_images );
		}
		wp_reset_postdata();
	}

	// Add taxomomies
	$taxonomies = csg_get_included( "taxonomies" );
	if ( $taxonomies ) {
		foreach( $taxonomies as $taxonomie ) {
			$terms = get_terms([
				'taxonomy' 		=> esc_html( $taxonomie ),
				'order' 		=> 'asc',
				'orderby' 		=> 'name',
				'hide_empty' 	=> true,
				'exclude' 		=> csg_get_excluded( "taxonomies" )
			]);
			if( $terms ) {
				foreach( $terms as $term ) {
					$csg_sitemap_content[] 	= csg_output_xml_line( $term->term_id, "term", $get_frequency, false );
				}
			}
		}
	}

	// Additional pages
	$additional_pages = rest_sanitize_array( csg_get_option( 'additionalpages' ) );
	foreach( $additional_pages as $page_url ) {
		$csg_sitemap_content[] 	= csg_output_xml_line( 0, $page_url, $get_frequency, false );
	}

	// End
	$csg_sitemap_content[] 	= "</urlset>";

	// esc_xml()
	return implode( "\n", $csg_sitemap_content );

	// return ent2ncr( $csg_sitemap_content );

 }

 // Prepare the URLs
 function csg_output_xml_line( int $id, string $type, string $get_frequency, bool $get_images ): string {

	// Get post information
	$output_line 	= [];
	$get_the_url 	= $type === 'term' ? get_term_link( $id ) : get_the_permalink( $id );
	$get_the_date 	= get_the_modified_date( 'Y-m-d', $id );

	// Additional pages, slightly 'hacky' but who cares
	if( $id === 0 ) {
		$get_the_url = $type;
		$get_the_date = date( 'Y-m-d' );
	}

	// Make sure url is not empty
	if( empty( $get_the_url ) ) {
		return null;
	}

	// Output
	$output_line[] = "<url>";
	$output_line[] = sprintf( "<loc>%s</loc>", esc_url( $get_the_url ) ); // Base URL

	// TODO: Add multilingual support back

	// Show last modified date
	$output_line[] = sprintf( "<lastmod>%s</lastmod>", esc_html( $get_the_date ) );

	// Show frequency if enabled
	if( $get_frequency !== 'hide' ) {
		$output_line[] = sprintf( "<changefreq>%s</changefreq>", esc_html( $get_frequency ) );
	}

	// Show images i enabled
	if( $get_images ) {
		$output_line[] = csg_output_post_images( (int) $id );
	}

	// End output
	$output_line[] = "</url>";

	// esc_xml()
	return implode( "", $output_line );


 }

 // Get images for a post
 function csg_output_post_images( int $id ): string {

	// Note: Sitemaps can only have up to a 1000 images per page (do you need more really?)
	$post_images = [];
	$attachments = get_children([
		'post_parent' 		=> sanitize_key( $id ),
		'post_status' 		=> 'inherit',
		'post_type' 		=> 'attachment',
		'post_mime_type' 	=> 'image',
		'posts_per_page' 	=> '1000'
	]);

	if( $attachments ) {
		foreach( $attachments as $att_id => $attachment ) {
			$post_images[] = sprintf(
				"<image:image><image:loc>%s</image:loc></image:image>",
				esc_url( wp_get_attachment_url( (int) $attachment->ID ) )
			);
		}
	}

	return implode( "", $post_images );

 }
