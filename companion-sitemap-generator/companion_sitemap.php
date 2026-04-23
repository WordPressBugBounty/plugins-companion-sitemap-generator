<?php
/*
 * Plugin Name: Companion Sitemap Generator
 * Plugin URI: https://wijzijnqreative.nl/
 * Description: Create & Edit an XML sitemap, HTML sitemap and robots.txt file
 * Version: 4.6.2
 *
 * Author: Papin Schipper
 * Author URI: https://wijzijnqreative.nl/
 * Contributors: papin
 *
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: companion-sitemap-generator
 * Domain Path: /languages/
*/

/*
* TODO:
* - Multilingual support
* - Improve the block
*/

// Disable direct access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Definitions
define( "CSG_DB_VERSION", "4.6.0" );
define( "CSG_DB_TABLE", "csg_sitemap" );
define( "CSG_DONATE_URL", "https://www.paypal.me/wijzijnqreative/" );

// Load translations
function csg_load_translations() {
	load_plugin_textdomain( 'companion-sitemap-generator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'csg_load_translations' );

// Check database version
function csg_db_version_matches(): bool {
	return (float) get_site_option( "csg_db_version" ) === (float) CSG_DB_VERSION;
}

// Create datbase and events on activations
function csg_install( $network_wide ) {

	global $wpdb;

	// On a multisite we'll create the database for each blog
    if( is_multisite() && $network_wide ) {

        $blog_ids = wp_get_sites();

        foreach( $blog_ids as $key => $current_blog ) {
            switch_to_blog( $current_blog['blog_id'] );

			// Create the database and installation hooks
            csg_startup_data();

			// Create the xml file
			csg_create_file( 'sitemap.xml' );

            restore_current_blog();
        }

    } else {

		// Create the database and installation hooks
        csg_startup_data();

		// Create the xml file
		csg_create_file( 'sitemap.xml' );

    }

	// Create an event for auto-updating
	if( ! wp_next_scheduled ( 'csg_create_sitemap' ) ) {
		wp_schedule_event( time(), 'daily', 'csg_create_sitemap '); // Set schedule for updating the sitemap
	}

	// Hook the sitemap function to the event
	add_action( 'csg_create_sitemap', 'csg_update_xml_sitemap' );


}
register_activation_hook( __FILE__, 'csg_install' );

// Create database table when new multisite blog is created
function csg_install_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    if ( is_plugin_active_for_network( 'companion-sitemap-generator/companion_sitemap.php' ) ) {

        switch_to_blog( $blog_id );

		// Create the database and installation hooks
		csg_startup_data();

		// Create the xml file
		csg_create_file( 'sitemap.xml' );

        restore_current_blog();

    }
}
add_action( 'wpmu_new_blog', 'csg_install_on_new_blog', 10, 6 );

// Set all the data needed for this plugim
function csg_startup_data() {

	global $wpdb;

	// DB table creation queries
	$sql = sprintf(
		"CREATE TABLE %s (
			id INT(9) NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			onoroff TEXT NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY unique_name (name)
		) %s;",
		$wpdb->prefix . CSG_DB_TABLE,
		$wpdb->get_charset_collate()
	);

	// Create DB tables
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Database version
	add_site_option( "csg_db_version", CSG_DB_VERSION );

	// Insert data
	csg_install_data();

	// Disable WordPress sitemaps
	add_filter( "wp_sitemaps_enabled", "__return_false" );

	// Updating..
	if( ! csg_db_version_matches() ) {
		delete_option( "csg_db_version" );
		update_site_option( "csg_db_version", CSG_DB_VERSION );
	}

}

// Insert Data
function csg_install_data() {

	global $wpdb;
	$table_field 	= array(

		'frequency' 				=> 'monthly', // XML frequency
		'use_sitemap_stylesheet' 	=> 'on', // Use stylesheet in the xml sitemap?
		'sitemap_stylesheet' 		=> '', // The URL for the stylesheet
		'additionalpages' 			=> '', // Add additional pages,
		'html_nag_dismissed' 		=> '', // Option to dismiss the nag about the html page

		// 'xml_in_html' 				=> '', // Display a link to the XML file in the HTML one?

		// These should really be renamed
		'exclude' 					=> '', // Exclude posts
		'posttypes' 				=> '', // Exclude posttypes and taxonomies
		'ctam' 						=> '', // Exclude terms

		// These should really be 1 item
		'ping_google' 				=> '', // Ping google
		'ping_bing' 				=> '',  // Ping bing
		'ping_yandex' 				=> '',  // Ping yandex
	);

	foreach( $table_field as $field => $default_value ) {
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO %i (name, onoroff) VALUES (%s, %s)",
				$wpdb->prefix . CSG_DB_TABLE,
				$field,
				$default_value
			)
		);
	}

}

// Clear everything
function csg_remove() {

	// Delete database table
	global $wpdb;
	$wpdb->query( $wpdb->prepare(
		"DROP TABLE IF EXISTS %i",
		$wpdb->prefix . CSG_DB_TABLE
	) );

	// Clear the custom events
	wp_clear_scheduled_hook( "csg_create_sitemap" );

	// Delete the db version option
	delete_site_option( "csg_db_version" );

	// Re-active WP sitemaps
	add_filter( "wp_sitemaps_enabled", "__return_true" );

}
register_deactivation_hook(  __FILE__, 'csg_remove' );

// Clear everything on multisites
function csg_multiste_remover( $tables ) {
    global $wpdb;
    $tables[] = $wpdb->prefix . CSG_DB_TABLE;
    return $tables;
}
add_filter( 'wpmu_drop_tables', 'csg_multiste_remover' );

// Update the database
function csg_update_db_check() {
    if ( get_site_option( 'csg_db_version' ) != CSG_DB_VERSION ) {
        csg_startup_data();
        update_site_option( "csg_db_version", CSG_DB_VERSION );
    }
}
add_action( 'plugins_loaded', 'csg_update_db_check' );

// Add help tab with more info
function csg_add_help_tab() {

	$screen 		= get_current_screen();

	// Obviously a temporary solution
	$help_tabs_test = array(
		esc_html__( 'Howdy!', 'companion-sitemap-generator' ) => '<p><strong>'.esc_html__( 'Thank you for using Companion Sitemap Generator', 'companion-sitemap-generator' ).'</strong></p><p>'.esc_html__( 'Companion Sitemap Generator is free to use. It has required a great deal of time and effort to develop and you can help support this development by making a small donation. You get useful software and we get to carry on making it better.', 'companion-sitemap-generator' ).'</p><p><a href="https://wordpress.org/support/plugin/companion-sitemap-generator/reviews/#new-post" target="_blank" class="button">'.esc_html__( 'Rate us', 'companion-sitemap-generator' ).'</a> <a href="'.CSG_DONATE_URL.'" target="_blank" class="button">'.esc_html__( 'Donate to help development', 'companion-sitemap-generator' ).'</a></p>',
		esc_html__( 'Sitemap submission', 'companion-sitemap-generator' ) => '<p><strong>'.esc_html__( 'Notify search engines', 'companion-sitemap-generator' ).'</strong></p><p>'.esc_html__( 'We can notify search engines when changes are made to your sitemap', 'companion-sitemap-generator' ).'.</p><p>'.sprintf( esc_html__( 'You can also submit your xml sitemap to search engines to help them better crawl your site. Submit the following url: %s', 'companion-sitemap-generator' ), csg_get_file_url( 'sitemap' ) ).'</p><p><a href="https://support.google.com/webmasters/answer/183668" target="_blank" class="button">'.sprintf( esc_html__( 'Submit a sitemap to %s', 'companion-sitemap-generator' ), 'Google' ).'</a> <a href="https://www.bing.com/webmaster/help/how-to-submit-sitemaps-82a15bd4" target="_blank" class="button">'.sprintf( esc_html__( 'Submit a sitemap to %s', 'companion-sitemap-generator' ), 'Bing' ).'</a></p>',
	);

	foreach( $help_tabs_test as $title => $content ) {
		$tab_id 		= sanitize_key( $title );
		$tab_title 		= esc_attr( $title );
		$tab_content 	= wp_kses_post( $content );

		$screen->add_help_tab( array( 'id' => $tab_id, 'title' => $tab_title, 'content' => $tab_content ) );
	}

    // Add help tabs
    $screen->add_help_tab( [ 'id' => 'csg_about_styling', 'title' => esc_html__( 'Sitemap styling', 'companion-sitemap-generator' ), 'content' =>
		'<p>Obviously there needs to be more content added here, but for now you can learn more about XML sitemap styling by clicking the link below :)</p>
		<p><a href="https://www.w3schools.com/xml/xsl_intro.asp" target="_blank" rel="nofollow">Learn more</a></p>',
    ] );

    // Add help tabs
    $screen->add_help_tab( [ 'id' => 'csg_about_robots', 'title' => esc_html__( 'Robots' ), 'content' =>
		'<p>Obviously there needs to be more content added here, but for now you can learn more about robots by clicking the link below :)</p>
		<p><a href="https://support.google.com/webmasters/answer/6062608" target="_blank" rel="nofollow">Learn more</a></p>',
    ] );

    // Add a sidebar to the help tab
    $screen->set_help_sidebar(
		'<p><a href="https://wordpress.org/support/plugin/companion-sitemap-generator/" target="_blank">'.esc_html__( 'Support Forums', 'companion-sitemap-generator' ).'</a></p>'.
		'<p><a href="https://wordpress.org/support/plugin/companion-sitemap-generator/reviews/#new-post" target="_blank">'.esc_html__( 'Rate us', 'companion-sitemap-generator' ).'</a></p>'.
		'<p><a href="https://translate.wordpress.org/projects/wp-plugins/companion-sitemap-generator/" target="_blank">'.esc_html__( 'Help us translate', 'companion-sitemap-generator' ).'</a></p>'.
		'<p><a href="https://profiles.wordpress.org/papin/" target="_blank">'.esc_html__( 'Check out our other plugins', 'companion-sitemap-generator' ).'</a></p>'
    );

}
add_action( 'load-tools_page_csg-sitemap', 'csg_add_help_tab' );

// Load admin styles
function load_csg_styles( $hook ) {

	// Only run on pages of this plugin, obviously
    if( $hook == 'tools_page_csg-sitemap' ) {

		// Add CSS file
	    wp_enqueue_style( 'csg-admin-style', plugins_url( 'backend/style.css', __FILE__ ) );

		// Add inline script
		wp_register_script( 'csg-admin-script', '' );
		wp_enqueue_script( 'csg-admin-script' );
		$minified_js = 'function csg_table_state(t){let e=t.getAttribute("data-objectlist"),i=document.getElementById(e+"-list");i&&(""!==t.value?(i.classList.add("inactive")):(i.classList.remove("inactive")))}';
		wp_add_inline_script( 'csg-admin-script', $minified_js );

	}

}
add_action( 'admin_enqueue_scripts', 'load_csg_styles' );

// Add class to the body tag, we're copying some styling from site-health
function csg_admin_body_class( $classes ) {
	if( get_current_screen()->base === 'tools_page_csg-sitemap' ) {
    	$classes .= ' site-health sitemap-generator ';
	}
    return $classes;
}
add_filter( 'admin_body_class', 'csg_admin_body_class' );

// Add to menu
function csg_menu_items(){
	add_submenu_page( 'tools.php', esc_html__( 'Sitemap', 'companion-sitemap-generator' ), esc_html__( 'Sitemap', 'companion-sitemap-generator' ), 'manage_options', 'csg-sitemap', 'csg_dashboard' );
}
add_action( 'admin_menu', 'csg_menu_items' );

// Add generate sitemap link on plugin page
function csg_settings_link( $links ) {
	$links[] = '<a href="tools.php?page=csg-sitemap">'.esc_html__( 'Settings', 'companion-sitemap-generator' ).'</a>';
	$links[] = '<a href="https://translate.wordpress.org/projects/wp-plugins/companion-sitemap-generator" target="_blank">'.esc_html__( 'Help us translate', 'companion-sitemap-generator' ).'</a>';
	return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter( "plugin_action_links_$plugin", "csg_settings_link" );

// Sitemap dashboard
function csg_dashboard() {

	// Get the sub-pages
	$load_tab 		= isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
	$admin_screens 	= [
		'dashboard' 		=> esc_html__( 'Dashboard', 'companion-sitemap-generator' ),
		'content-filter' 	=> esc_html__( 'Content filter', 'companion-sitemap-generator' ),
	];

	// Safety check
	if( ! isset( $admin_screens[$load_tab] ) ) {
		wp_die( "You're not allowed to view <strong>{$load_tab}</strong>." );
	}

	// Start output
	echo "<div class='sitemap-generator-header'><div class='sitemap-generator-wrapper'>";

		echo sprintf(
			"<h1 class='sitemap-generator-title'>%s</h1>",
			esc_html__( "Companion Sitemap Generator", "companion-sitemap-generator" )
		);

		echo "<nav class='nav-tab-wrapper sitemap-nav-tab-wrapper'>";
			foreach ( $admin_screens as $screen => $title ) {
				echo sprintf(
					"<a href='%s' class='sitemap-generator-tab' %s>
						%s
					</a>",
					sprintf( "tools.php?page=csg-sitemap&tab=%s", esc_html( $screen ) ),
					$load_tab === $screen ? "aria-current='page'" : "",
					esc_html( $title )
				);
			}
		echo "</nav>";

	echo "</div></div>";

	// Start the content
	echo "<div class='sitemap-generator-wrapper'>";
		require_once sprintf( "%s/dashboard/%s.php", __DIR__, $load_tab );
	echo "</div>";

}

// Load the general functions
require_once __DIR__ . "/functions-general.php";

// Load the XML sitemap functions
require_once __DIR__ . "/functions-sitemap-xml.php";

// Load the HTML sitemap functions
require_once __DIR__ . "/functions-sitemap-html.php";

// Load the robots functions
require_once __DIR__ . "/functions-robots.php";

// Integrate with Site Health
require_once __DIR__ . "/functions-site-health.php";

// Add multilingual support
require_once __DIR__ . "/functions-languages.php";

// Add gutenberg block (and shortcode)
require_once __DIR__ . "/functions-block.php";
