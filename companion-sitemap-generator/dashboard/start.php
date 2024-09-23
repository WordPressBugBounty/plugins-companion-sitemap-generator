
<div class="wrap csg_content">

	<h1 class="wp-heading-inline"><?php _e( 'Companion Sitemap Generator', 'companion-sitemap-generator' ); ?></h1>

	<hr class="wp-header-end">

	<h2 class="nav-tab-wrapper wp-clearfix">
		<a href="<?php echo admin_url( 'tools.php?page=csg-sitemap'); ?>" class="nav-tab <?php csg_active_tab(''); ?>"><?php _e( 'Dashboard', 'companion-sitemap-generator'); ?></a>
		<a href="<?php echo admin_url( 'tools.php?page=csg-sitemap&amp;tabbed=additionalpages'); ?>" class="nav-tab <?php csg_active_tab('additionalpages'); ?>"><?php _e( 'Additional pages', 'companion-sitemap-generator'); ?></a>
		<a href="<?php echo admin_url( 'tools.php?page=csg-sitemap&amp;tabbed=exclusion'); ?>" class="nav-tab <?php csg_active_tab('exclusion'); ?>"><?php _e( 'Content filter', 'companion-sitemap-generator'); ?></a>
	</h2>

	<?php 

	// Default
	if( !isset( $_GET['tabbed'] ) ) { 
		require_once plugin_dir_path( __FILE__ ).'dashboard.php';

	} else {

		// Check for allowed pages
		$requestedPage 	= sanitize_key( $_GET['tabbed'] );
		$allowedPages 	= array( 'dashboard', 'exclusion', 'additionalpages' );

		if( in_array( $requestedPage, $allowedPages) ) {
			require_once plugin_dir_path( __FILE__ ) .''.$requestedPage.'.php';
		} else {
			wp_die( 'You\'re not allowed to view <strong>'.$requestedPage.'</strong>.' );				
		}

	} ?>

</div>