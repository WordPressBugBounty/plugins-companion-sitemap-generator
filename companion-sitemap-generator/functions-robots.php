<?php
/**
 * Robots Functions
 *
 * This file contains functions regarding the robots.txt file and editor
 *
 *
 * @see     https:/wijzijnqreative.nl/url-to-be-added/
 * @version 4.6.0
 */

 // Get the path to the robots file
 function csg_get_robots() {
	$robots_file = get_home_path() . csg_get_file_name( 'robots.txt' );
	return file_exists( $robots_file  )
		? esc_url( $robots_file )
		: false;
 }

// Default robots content
function csg_default_robots(): string {

    $robots_example_content   = ["# Block WordPress directories"];
    $robots_example_content[] = "User-agent: *";
    $robots_example_content[] = "Disallow: /wp-admin/";
    $robots_example_content[] = "Disallow: /wp-includes/";
    $robots_example_content[] = "Disallow: /wp-content/cache/";
    $robots_example_content[] = "Disallow: /cgi-bin/";

    $robots_example_content[] = "";

    $robots_example_content[] = "# Block WordPress directories -- Optional";
    $robots_example_content[] = "Disallow: /feed/";
    $robots_example_content[] = "Disallow: */feed/";
    $robots_example_content[] = "Disallow: */trackback/";
    $robots_example_content[] = "Disallow: */comments/";

    $robots_example_content[] = "";

    $robots_example_content[] = "# Allow WordPress directories";
    $robots_example_content[] = "Allow: /wp-admin/admin-ajax.php";
    $robots_example_content[] = "Allow: /wp-content/plugins/";
    $robots_example_content[] = "Allow: /wp-content/themes/";

    $robots_example_content[] = "";

    $robots_example_content[] = "# Block searches (preventing duplicate content)";
    $robots_example_content[] = "Disallow: /?s=";
    $robots_example_content[] = "Disallow: /search/";

    $robots_example_content[] = "";

    $robots_example_content[] = "# Allow media";
    $robots_example_content[] = "Allow: /wp-content/uploads/";

    // On multisite add each of the sitemaps
    if( is_multisite() ) {
        $sites = get_sites();
        foreach( $sites as $site ) {
            switch_to_blog( $site->blog_id );
            $blog_name = get_bloginfo( 'name' );
            $robots_example_content[] = "";
            $robots_example_content[] = "# Sitemap for {$blog_name}";
            $robots_example_content[] = "Sitemap: ".esc_url( csg_get_file_url( 'sitemap.xml' ) );
            restore_current_blog();
        }
    } else {

        $robots_example_content[] = "";
        $robots_example_content[] = "# Sitemap";
        $robots_example_content[] = "Sitemap: ".esc_url( csg_get_file_url( 'sitemap.xml' ) );

    }

    return implode( "\n", $robots_example_content );

}
