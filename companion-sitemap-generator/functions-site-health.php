<?php 
/**
 * Site Health Integration
 * 
 * This file add a few site health integrations for status info
 *
 *
 * @see     https:/wijzijnqreative.nl/url-to-be-added/
 * @version 4.6.0
 */

// Show debug information in site health
function csg_sitehealth_integration( $debug_info ) {

    $fields = array(
        'can_write_xml' => array(
            'label' => 'sitemap.xml',
            'value' => csg_xml_status()
        ),
        'can_write_robots' => array(
            'label' => 'robots.txt',
            'value' => csg_robots_status()
        ),
        'can_ping' => array(
            'label' => esc_html__( 'Can notify search engines?', 'companion-sitemap-generator' ),
            'value' => csg_can_ping() ? esc_html__( 'Yes' ) : esc_html__( 'No' )
        )
    );

    $debug_info['sitemap'] = array(
        'label'         => esc_html__( 'Sitemap', 'companion-sitemap-generator' ),
        'description'   => esc_html__( 'Reported by the Companion Sitemap Generator plugin', 'companion-sitemap-generator' ),
        'fields'        => $fields,
    );

    return $debug_info;
}
add_filter( 'debug_information', 'csg_sitehealth_integration' );

// Gets the status of the xml file
function csg_xml_status() {
    
    // File doesn't exist (yet)
    if( ! csg_get_sitemap() ) {
        return esc_html__( 'Does not exist' );
    }

    if ( ! is_writable( csg_get_sitemap() ) ) {    
        return esc_html__( 'Not writable' );
    }

    return esc_html__( 'Writable' );

}

// Gets the status of the robots file
function csg_robots_status() {
    
    // File doesn't exist (yet)
    if( ! csg_get_sitemap() ) {
        return esc_html__( 'Does not exist' );
    }

    if ( ! is_writable( csg_get_sitemap() ) ) {    
        return esc_html__( 'Not writable' );
    }

    return esc_html__( 'Writable' );

}

// Checks if cUrls is active (needed to ping search engines)
function csg_can_ping() {
    return in_array( 'curl', get_loaded_extensions() );
}