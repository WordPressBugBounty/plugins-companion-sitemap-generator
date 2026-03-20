<?php	

// Get current page / tab information
$load_tab 	= ! isset( $_GET['subtab'] ) ? 'wp' : sanitize_key( $_GET['subtab'] );
$page_url 	= esc_url( admin_url( 'tools.php?page=csg-sitemap&tab=content-filter' ) );
$admin_tabs 	= array( 
	'wp' 			=> __( 'Posts', 'companion-sitemap-generator' ), 
	'additional' 	=> __( 'Additional pages', 'companion-sitemap-generator' ), 
);

// Security check
if( ! isset( $admin_tabs[$load_tab] ) ) {
	wp_die( "You're not allowed to view <strong>{$load_tab}</strong>." );
}

// Begin the output
echo "<ul class='subsubsub'>";
    foreach( $admin_tabs as $tab => $title ) {
        $tab 	    = esc_attr( $tab );
        $title 		= esc_attr( $title );
        $current 	= ( $load_tab == $tab ) ? 'class="current" aria-current="page"' : '';
        echo "<li><a href='{$page_url}&subtab={$tab}' {$current}>{$title}</a></li>";
        if( array_key_last( $admin_tabs ) != $tab ) echo " | ";
    }
echo "</ul>";

// Load the correct file
require_once __DIR__ . "/filter-{$load_tab}-content.php";
