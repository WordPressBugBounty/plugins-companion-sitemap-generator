<?php
/*
 * Plugin Name: Companion Sitemap Generator
 * Plugin URI: https://plugins.wijzijnqreative.nl/plugin/companion-sitemap-generator/
 * Description: Easy to use XML & HTML sitemap generator and robots editor.
 * Version: 4.5.9.3
 * Author: Papin Schipper
 * Author URI: https://plugins.wijzijnqreative.nl/
 * Contributors: papin
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: companion-sitemap-generator
 * Domain Path: /languages/
*/

// Disable direct access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Load translations
function csg_load_translations() {
	load_plugin_textdomain( 'companion-sitemap-generator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}
add_action( 'init', 'csg_load_translations' );

// Adds styling
function csg_frontend_style() {
	wp_register_style( 'csg-styling', plugin_dir_url( __FILE__ ) . 'frontend/style.css', array(), '1.0.0', 'all'  );
}
add_action( 'wp_enqueue_scripts', 'csg_frontend_style' );

// Create datbase and events on activations
function csg_install( $network_wide ) {

	global $wpdb;

    if ( is_multisite() && $network_wide ) {

        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            csg_database_creation();
            restore_current_blog();
        }

    } else {
        csg_database_creation();
    }

	if( !wp_next_scheduled ( 'csg_create_sitemap' ) ) wp_schedule_event( time(), 'hourly', 'csg_create_sitemap '); // Set schedule for updating the sitemap

	// Root
	$csg_website_root = get_home_path();

	// Create sitemap(s)
	if( is_multisite() ) {
		foreach ( get_sites() as $site ) {
	        $subsite_id 		= $site->blog_id;
			$csg_sitemap_file 	= $csg_website_root.'/'.csg_sitemap_file( true, $subsite_id );
			if ( !file_exists( $csg_sitemap_file ) ) $csg_myfile = fopen( $csg_sitemap_file, "w" );
	    }
	} else {
		$csg_sitemap_file 	= $csg_website_root.'/'.csg_sitemap_file();
		if ( !file_exists( $csg_sitemap_file ) ) $csg_myfile = fopen( $csg_sitemap_file, "w" );
	}


}
add_action( 'csg_create_sitemap', 'csg_sitemap' );

// Robots file
function csg_robots() {
	return get_home_path() . '/robots.txt';
}

// Check if robots file exists
function csg_has_robots() {
	return file_exists( csg_robots() ) ? true : false;
}

// Create robots file
function csg_create_robots() {
	if ( !file_exists( csg_robots() ) ) $csg_robots_myfile = fopen( csg_robots(), "w+" );
}

// Create database table when new multisite blog is created
function csg_newBlogCreation( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    if ( is_plugin_active_for_network( 'companion-sitemap-generator/companion_sitemap.php' ) ) {
        switch_to_blog( $blog_id );
        csg_database_creation();
        restore_current_blog();
    }
}
add_action( 'wpmu_new_blog', 'csg_newBlogCreation', 10, 6 );

// Database version
function csg_db_version() {
	return '4.5.9';
}

// Run database creator
function csg_database_creation() {

	global $wpdb;

	// Database information
	$csg_db_version 	= csg_db_version();
	$table_name 		= $wpdb->prefix . "csg_sitemap"; 
	$charset_collate 	= $wpdb->get_charset_collate();

	// DB table creation queries
	$sql = "CREATE TABLE $table_name (
		id INT(9) NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) NOT NULL,
		onoroff TEXT NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	// Create DB tables
	require_once ABSPATH.'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Database version
	add_option( "csg_db_version", "$csg_db_version" );

	// Insert data
	csg_install_data();

	// Disable WP-sitemaps
	add_filter( 'wp_sitemaps_enabled', '__return_false' );

	// Updating..
	$installed_ver = get_option( "csg_db_version" );
	if ( $installed_ver != $csg_db_version ) update_option( "csg_db_version", $csg_db_version );

}

// Check database version
function csg_incorrectDatabaseVersion() {
	return ( get_option( "csg_db_version" ) != csg_db_version() ) ? true : false;
}

// Check if database table exists before creating
function csg_check_if_exists( $whattocheck ) {

	global $wpdb;
	$table_name = $wpdb->prefix . "csg_sitemap"; 
	$rows 		= $wpdb->get_col( "SELECT COUNT(*) as num_rows FROM {$table_name} WHERE name = '{$whattocheck}'" );
	$check 		= $rows[0];

	return ( $check > 0 ) ? true : false;

}

// Insert Data
function csg_install_data() {

	global $wpdb;
	$table_name = $wpdb->prefix . "csg_sitemap"; 

	if( !csg_check_if_exists( 'exclude' ) ) 				$wpdb->insert( $table_name, array( 'name' => 'exclude', 'onoroff' => '' ) ); // Exclude POSTS
	if( !csg_check_if_exists( 'posttypes' ) ) 				$wpdb->insert( $table_name, array( 'name' => 'posttypes', 'onoroff' => '' ) ); // Exclude Posttypes
	if( !csg_check_if_exists( 'ctam' ) ) 					$wpdb->insert( $table_name, array( 'name' => 'ctam', 'onoroff' => '' ) ); // Exclude Categories, Tags and More

	if( !csg_check_if_exists( 'frequency' ) ) 				$wpdb->insert( $table_name, array( 'name' => 'frequency', 'onoroff' => 'monthly' ) ); // Frequency

	if( !csg_check_if_exists( 'sitemap_stylesheet' ) ) 		$wpdb->insert( $table_name, array( 'name' => 'sitemap_stylesheet', 'onoroff' => '' ) ); // Stylesheet URL
	if( !csg_check_if_exists( 'use_sitemap_stylesheet' ) ) 	$wpdb->insert( $table_name, array( 'name' => 'use_sitemap_stylesheet', 'onoroff' => 'on' ) ); // Use the stylesheet or not?

	if( !csg_check_if_exists( 'xml_in_html' ) ) 			$wpdb->insert( $table_name, array( 'name' => 'xml_in_html', 'onoroff' => '' ) ); // Display the XML sitemap in the HTML one?

	if( !csg_check_if_exists( 'additionalpages' ) ) 		$wpdb->insert( $table_name, array( 'name' => 'additionalpages', 'onoroff' => '' ) ); // Additional pages

	if( !csg_check_if_exists( 'ping_google' ) ) 			$wpdb->insert( $table_name, array( 'name' => 'ping_google', 'onoroff' => '' ) ); // Ping Google
	if( !csg_check_if_exists( 'ping_bing' ) ) 				$wpdb->insert( $table_name, array( 'name' => 'ping_bing', 'onoroff' => '' ) ); // Ping Bing
	if( !csg_check_if_exists( 'ping_yandex' ) ) 			$wpdb->insert( $table_name, array( 'name' => 'ping_yandex', 'onoroff' => '' ) ); // Ping Yandex

}
register_activation_hook( __FILE__, 'csg_install' );

// Clear everything
function csg_remove() {

	// Delete database table
	global $wpdb;
	$table_name = $wpdb->prefix . "csg_sitemap"; 
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

	// Clear the custom events
	wp_clear_scheduled_hook( 'csg_create_sitemap' );

	// Re-active WP sitemaps
	add_filter( 'wp_sitemaps_enabled', '__return_true' );

}
register_deactivation_hook(  __FILE__, 'csg_remove' );

// Remove table when blog is deleted
function csg_removeMultisite( $tables ) {
    global $wpdb;
    $tables[] = $wpdb->prefix . "csg_sitemap";
    return $tables;
}
add_filter( 'wpmu_drop_tables', 'csg_removeMultisite' );

// Update the database
function csg_update_db_check() {
    if ( get_site_option( 'csg_db_version' ) != csg_db_version() ) {
        csg_database_creation();
        update_option( "csg_db_version", csg_db_version() );
    }
}
add_action( 'plugins_loaded', 'csg_update_db_check' );

// Manual update
function csg_manual_update() {
	csg_update_db_check();
}

// Load admin styles
function load_csg_styles( $hook ) {
    if( $hook == 'tools_page_csg-sitemap' ) {
	    wp_enqueue_style( 'csg_admin_styles', plugins_url('backend/style.css', __FILE__) );
	}
}
add_action( 'admin_enqueue_scripts', 'load_csg_styles' );

// Add to menu
function csg_menu_items(){
	add_submenu_page( 'tools.php', __( 'Sitemap', 'companion-sitemap-generator' ), __( 'Sitemap', 'companion-sitemap-generator' ), 'manage_options', 'csg-sitemap', 'csg_dashboard' );
}
add_action( 'admin_menu', 'csg_menu_items' );

// Add generate sitemap link on plugin page
function csg_settings_link( $links ) { 
	$links[] = '<a href="tools.php?page=csg-sitemap">'.__( 'Settings', 'companion-sitemap-generator' ).'</a>'; 
	$links[] = '<a href="https://translate.wordpress.org/projects/wp-plugins/companion-sitemap-generator" target="_blank">'.__( 'Help us translate', 'companion-sitemap-generator' ).'</a>'; 
	return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter( "plugin_action_links_$plugin", "csg_settings_link" );

// Load functions
require 'csg_functions.php';

// Sitemap dashboard
function csg_dashboard() {
	require 'dashboard/start.php';
}

// Create widget
require 'dashboard/widget.php';

// Skip block registration if Gutenberg is not enabled/merged.
if ( function_exists( 'register_block_type' ) ) {
	require 'csg_gutenberg.php';
}
