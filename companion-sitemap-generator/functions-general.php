<?php

/**
 * General Functions
 *
 * This file contains functions regarding multiple features
 *
 *
 * @see     https:/wijzijnqreative.nl/url-to-be-added/
 * @version 4.6.0
 */

 // Get settings
 function csg_get_option( string $option ) {
	global $wpdb;
	$table_name = $wpdb->prefix . "csg_sitemap";
	$value 		= $wpdb->get_var( $wpdb->prepare( "SELECT onoroff FROM {$table_name} WHERE name = %s", sanitize_text_field( $option ) ) );
	return ! empty( $value ) ? $value : false;
 }

 // Check if a setting is enabled
 function csg_option_enabled( string $option ): bool {
	return csg_get_option( $option ) === 'on';
 }

 // Get the file name of the correct sitemap file or robots file
 function csg_get_file_name( string $which ): string {

	global $blog_id;
	$which = esc_attr( $which );

	// Get the correct sitemap (multisite supported)
	$sitemap = ( $blog_id > 1 )
		? sprintf( "sitemap-%d.xml", $blog_id )
		: "sitemap.xml";

	// Get the robots file name
	$robots = "robots.txt";

	// Return the correct file name
	return ( $which === 'robots.txt' )
		? $robots
		: $sitemap;

 }

 // Creates the file if it doesn't exist already
 function csg_create_file( string $which ) {

	$which 	= esc_attr( $which );
	$file 	= ( $which == 'robots.txt' )
		? csg_get_robots()
		: csg_get_sitemap();

	// Is home path writable?
	if ( ! is_writable( get_home_path() ) ) {
		wp_admin_notice(
			sprintf( esc_html__( 'Could not write the %s file', 'companion-sitemap-generator' ), $which ),
			[ "type" => "error" ]
		);
		return;
	}

	// Does it already exist?
	if ( $file !== false ) {
		wp_admin_notice(
			sprintf( esc_html__( 'The %s file already exists', 'companion-sitemap-generator' ), $which ),
			[ "type" => "error" ]
		);
		return;
	}

	// Create the file
	if( !touch( $file ) ) {
		wp_admin_notice(
			sprintf( esc_html__( 'Could not create the %s file', 'companion-sitemap-generator' ), $which ),
			[ "type" => "error" ]
		);
		return;
	}

	wp_admin_notice(
		sprintf( esc_html__( 'Successfully saved the %s file', 'companion-sitemap-generator' ), $which ),
		[ "type" => "success" ]
	);

 }

 // Read the contents of a file
 function csg_read_file( string $which ) {

	$which 	= esc_attr( $which );
	$file 	= ( $which == 'robots.txt' ) ? csg_get_robots() : csg_get_sitemap();

	if( ! is_readable( $file ) ) {
		wp_admin_notice(
			sprintf( esc_html__( 'Could not read the %s file', 'companion-sitemap-generator' ), $which ),
			[ "type" => "error" ]
		);
		return;
	}

	return esc_textarea( file_get_contents( $file ) );

 }

 // Writes new content to a file
 function csg_write_file( string $which, string $new_content ) {

	// Get the correct file and correct sanitize method
	$which 		= esc_attr( $which );
	$file 		= ( $which === 'robots.txt' ) ? csg_get_robots() : csg_get_sitemap();
	$content 	= ( $which === 'robots.txt' ) ? sanitize_textarea_field( $new_content ) : $new_content;

	// Is file writable?
	if ( ! is_writable( $file ) ) {
		return '<div class="notice notice-error"><p>'.sprintf( esc_html__( 'Could not write the %s file', 'companion-sitemap-generator' ), $which ).'</p></div>';
	}

	// Write to the file
	$bytes = file_put_contents( $file, $content );

	// Writing succeeded
	if ( $bytes !== false ) {
		return '<div class="notice notice-success"><p>'.sprintf( esc_html__( 'Successfully saved the %s file', 'companion-sitemap-generator' ), $which ).'</p></div>';
		csg_ping_engines(); // Ping search engines

	} else {
		return '<div class="notice notice-error"><p>'.sprintf( esc_html__( 'Could not write the %s file', 'companion-sitemap-generator' ), $which ).'</p></div>';

	}

 }

 // Get the public url for a file
 function csg_get_file_url( string $which ): string {
	$which 			= esc_attr( $which );
	$site_url 		= is_multisite() ? network_site_url() : get_site_url();
	$file_name 		= csg_get_file_name( $which );
	$file_url 		= trailingslashit( $site_url ).$file_name;
	return esc_url( $file_url );
 }

 // Get only unique schedules
 function csg_wp_get_schedules(): array {

	// Start variables
	$availableIntervals = wp_get_schedules();
	$array_unique 		= [];
	$intervalTimes 		= [];
	$intervalNames 		= [];
	$intervalUniques 	= [];
	$counter 			= 0;

	// Get all intervals
	foreach ( $availableIntervals as $key => $value ) {

		// Do a bunch of checks to format them the right way
		foreach ( $value as $display => $interval ) {

			if( $display == 'interval' ) {

				if( $interval == '86400' ) $key = 'daily'; // Force the daily interval to be called daily, required by a bunch of handles of this plugin

				$intervalTimes[$counter] 	= $key;  // Add the backend name (i.e. "once_monthly" or "daily")
				$intervalUniques[$counter] 	= $interval;  // Add the unix timestamp of this interval, used to identify unique items

				// Format display name in a proper way
				$numOfMinutes 	= ($interval/60);
				$identifier 	= esc_html__( 'minutes', 'companion-sitemap-generator' );

				// I just know there's an easier way for this, but I can't come up with it and this works so...
				if( $interval >= (60*60) ) {
					$numOfMinutes 	= ($numOfMinutes/60);
					$identifier 	= esc_html__( 'hours', 'companion-sitemap-generator' );
				}
				if( $interval >= (60*60*24) ) {
					$numOfMinutes 	= ($numOfMinutes/24);
					$identifier 	= esc_html__( 'days', 'companion-sitemap-generator' );
				}
				if( $interval >= (60*60*24*7) ) {
					$numOfMinutes 	= ($numOfMinutes/7);
					$identifier 	= esc_html__( 'weeks', 'companion-sitemap-generator' );
				}
				if( $interval >= (60*60*24*7*(52/12)) ) {
					$numOfMinutes 	= ($numOfMinutes/(52/12));
					$identifier 	= esc_html__( 'months', 'companion-sitemap-generator' );
				}

				/* translators: interval, like every "two days" or every "five weeks" */
				$display 					= sprintf( esc_html__( 'Every %1$s %2$s', 'sitemap-generator' ), round( $numOfMinutes, 2 ), $identifier ); // Translateble
				$intervalNames[$counter] 	= $display; // Add the display name (i.e. "Once a month" or "Once Daily")

				$counter++; // Make sure the next interval gets a new "key" value
			}

		}

	}

	// Sort the interval from smallest to largest
	asort( $intervalUniques );

	// Prevent duplicates
	foreach ( array_unique( $intervalUniques ) as $key => $value ) {
		// $value is the timestamp
		// $intervalTimes[$key] is the backend name
		// $intervalNames[$key] is the display name
		$array_unique[$intervalTimes[$key]] = $intervalNames[$key];
	}

	// Return the array
	return $array_unique;

 }

 // Get all items by type
 function csg_get_all( string $type ): array {

	$match_types = [
		"post_ids" 		=> [],
		"post_types" 	=> get_post_types( ['public' => true], 'names', 'and' ),
		"taxonomies" 	=> get_taxonomies( ['public' => true], 'names', 'and' ),
	];

	if( ! isset( $match_types[$type] ) ) {
		return [];
	}

	return $match_types[$type];
 }

 // Get excluded items by type
 function csg_get_excluded( string $type ): array {

	$match_types = [
		"post_ids" 		=> "exclude",
		"post_types" 	=> "posttypes",
		"taxonomies" 	=> "ctam"
	];

	if( ! isset( $match_types[$type] ) ) {
		return [];
	}

	global $wpdb;
	$table_name = $wpdb->prefix . CSG_DB_TABLE;

	$exclusion_query = $wpdb->get_var( $wpdb->prepare(
		"SELECT onoroff FROM %i WHERE name = '%s' LIMIT 1",
		$table_name,
		$match_types[$type]
	) );

	return rest_sanitize_array( esc_html( $exclusion_query ) );

 }

 // Get included items by type
 function csg_get_included( string $type ): array {
	return array_diff( csg_get_all( $type ), csg_get_excluded( $type ) );
 }
