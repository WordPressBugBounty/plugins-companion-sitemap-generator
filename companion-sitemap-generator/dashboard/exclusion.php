<?php	

// Succes message
function csg_select_succes( $excludeCounter ) {

	echo '<div id="message" class="updated"><p>
		'.sprintf( esc_html__( '%s unchecked item(s) will no longer be added to your sitemap.', 'companion-sitemap-generator' ), $excludeCounter ).'.</p>
	</div>';

}

// Allow only access to these pages
$allowedPages = array( 
	'posttypes' 	=> __( 'Post Types' ), 
	'select' 		=> __( 'Posts', 'companion-auto-update' ), 
	'types' 		=> __( 'Categories, Tags and more', 'companion-auto-update' ), 
);

// Show subtabs
echo "<ul class='subsubsub'>";
$i = 0;
foreach ( $allowedPages as $page => $title ) {
	if( $i != '0' ) echo " | ";
	echo "<li class='".$page."-sub'><a href='".admin_url( 'tools.php?page=csg-sitemap&amp;tabbed=exclusion&subtabt='.$page )."'>".$title."</a></li>";
	$i++;
}
echo "</ul>";

echo "<br /><br /><hr />";
	
// Show page content
$requestedPage = !isset( $_GET['subtabt'] ) ? 'posttypes' : sanitize_key( $_GET['subtabt'] );

if( array_key_exists( $requestedPage, $allowedPages ) ) {

	echo "<h2>".$allowedPages[$requestedPage]."</h2>";
	echo "<p>".sprintf( esc_html__( 'Here you can select %s that you do not want to include in your sitemap.', 'companion-sitemap-generator' ), strtolower( $allowedPages[$requestedPage] ) )."</p>";

	require_once plugin_dir_path( __FILE__ ) . 'exclusion-'.$requestedPage.'.php';

	echo "<script>jQuery('.".$requestedPage."-sub a').addClass('current');</script>";

} else {
	wp_die( 'You\'re not allowed to view <strong>'.$requestedPage.'</strong>.' );				
}
