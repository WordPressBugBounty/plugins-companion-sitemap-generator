<?php

// Dynamic button text for widget
function csg_dynamic_button() {

	// Translation
	$sitemap_trans 		= __( 'sitemap', 'companion-sitemap-generator' );
	$update_trans 		= sprintf( __( 'Update %s', 'companion-sitemap-generator' ), $sitemap_trans );
	$generate_trans 	= sprintf( __( 'Generate %s', 'companion-sitemap-generator' ), $sitemap_trans );

	$csg_sitemap_file 	= ABSPATH . csg_sitemap_file();
	$emptySM 			= file_get_contents( $csg_sitemap_file ); 

	return ( strlen( $emptySM ) == 0 ) ? $generate_trans : $update_trans;

}

// Add a widget to the dashboard.
function csg_sitemap_gen_add_dashboard_widget() {
	if ( current_user_can( 'manage_options' ) ) wp_add_dashboard_widget( 'csg-sitemap-gen', __( 'Sitemap', 'companion-sitemap-generator' ), 'csg_sitemap_gen_dashboard_widget' );	
}
add_action( 'wp_dashboard_setup', 'csg_sitemap_gen_add_dashboard_widget' );

// Create the function to output the contents of our Dashboard Widget.
function csg_sitemap_gen_dashboard_widget() {

	// Translation
	$sitemap_trans 		= __( 'sitemap', 'companion-sitemap-generator' );
	$view_trans 		= sprintf( __( 'View %s', 'companion-sitemap-generator' ), $sitemap_trans );

	$csg_sitemap_file 	= ABSPATH . csg_sitemap_file();
	$emptySM 			= file_get_contents( $csg_sitemap_file ); 

	echo ( strlen( $emptySM ) == 0 ) ? __( "You currently don't have a sitemap, click the button to generate one.", "companion-sitemap-generator" ) : __( "We'll update your sitemap every hour, but in case you'd like to update it manually you can do that here.", "companion-sitemap-generator" );

	$dashboard_url 	= admin_url( 'tools.php?page=csg-sitemap' );
	$button_tekst 	= csg_dynamic_button();
	$sitemap_url 	= csg_sitemap_url();

	echo "<p><a href='{$dashboard_url}' class='button button-primary'>{$button_tekst}</a> <a href='{$sitemap_url}'  class='button button-alt' target='_blank'>{$view_trans}</a></p>";
}
