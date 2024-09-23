<?php

// Update the database
if( isset( $_GET['run'] ) && $_GET['run'] == 'db_update' ) {
	csg_manual_update();
	echo '<div id="message" class="updated"><p><b>'.__( 'Database update completed' ).'</b></p></div>';
}

// Save settings
if( isset( $_POST['submit'] ) ) {

	check_admin_referer( 'csg_save_advanced' );

	// Save frequency
	global $wpdb;
	$table_name 			= $wpdb->prefix . "csg_sitemap"; 

	$changeFreq 			= isset( $_POST["frequency"] ) ? sanitize_text_field( $_POST['frequency'] ) : '';
	$use_sitemap_stylesheet = isset( $_POST["use_sitemap_stylesheet"] ) ? sanitize_text_field( $_POST['use_sitemap_stylesheet'] ) : '';
	$sitemap_stylesheet 	= isset( $_POST["sitemap_stylesheet"] ) ? sanitize_text_field( $_POST['sitemap_stylesheet'] ) : '';

	$submit_google 			= isset( $_POST["submit_google"] ) ? sanitize_text_field( $_POST['submit_google'] ) : '';
	$submit_bing 			= isset( $_POST["submit_bing"] ) ? sanitize_text_field( $_POST['submit_bing'] ) : '';
	$ping_yandex 			= isset( $_POST["submit_yandex"] ) ? sanitize_text_field( $_POST['submit_yandex'] ) : '';

	$xml_in_html 			= isset( $_POST["xml_in_html"] ) ? sanitize_text_field( $_POST['xml_in_html'] ) : '';

	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'frequency'", $changeFreq ) );
	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'use_sitemap_stylesheet'", $use_sitemap_stylesheet ) );
	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'sitemap_stylesheet'", $sitemap_stylesheet ) );

	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'ping_google'", $submit_google ) );
	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'ping_bing'", $submit_bing ) );
	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'ping_yandex'", $ping_yandex ) );
	
	$wpdb->query( $wpdb->prepare( "UPDATE $table_name SET onoroff = %s WHERE name = 'xml_in_html'", $xml_in_html ) );

	// Save schedule
	// Set variables
	$sitemap_sc = sanitize_text_field( $_POST['sitemap_schedule'] );

	// First clear schedule
	wp_clear_scheduled_hook('csg_create_sitemap');

	// Then set the new times
	if( $sitemap_sc != 'never' ) wp_schedule_event( time(), $sitemap_sc, 'csg_create_sitemap' );

	echo '<div id="message" class="updated"><p>'.__('Settings saved', 'companion-sitemap-generator' ).'.</p></div>';

}

// Update sitemap
if( isset( $_POST['csg_generate'] ) ) {
	check_admin_referer( 'csg_generate_sitemap' );
	csg_sitemap();
}

$sitemap_schedule 	= wp_get_schedule( 'csg_create_sitemap' );

// Translations
$sitemap_trans 		= __( 'sitemap', 'companion-sitemap-generator' );
$view_trans 		= sprintf( __( 'View %s', 'companion-sitemap-generator' ), $sitemap_trans );

?>

<div class="csg-column-wide">

	<?php if( csg_incorrectDatabaseVersion() ) { ?>
		<div class="welcome-to-csg gutenberg-bg csg-dashboard-box">
			<h3>Incorrect database version</h3>
			<p>You're running version <?php echo get_option( "csg_db_version" ); ?> but version <?php echo csg_db_version(); ?> is the latest.</p>
			<a href="tools.php?page=csg-sitemap&run=db_update" class="button button-alt" style="background: #FFF;"><?php _e( 'Run updater now', 'companion-sitemap-generator' ); ?></a>
		</div>
	<?php } ?>
	
	<div class="welcome-to-csg welcome-bg csg-dashboard-box">

		<h3><?php _e( 'You\'re set and ready to go', 'companion-sitemap-generator' ); ?></h3>
		<p><strong><?php _e( 'Sitemap link' ); ?>:</strong> <a href='<?php echo csg_sitemap_url(); ?>' target='_blank'><?php echo csg_sitemap_url(); ?></a></p>
		<form method="POST">
			<p>
				<?php wp_nonce_field( 'csg_generate_sitemap' ); ?>
				<a href='<?php echo csg_sitemap_url(); ?>' target='_blank' class='button button-primary'><?php echo $view_trans; ?></a>
				<button type="submit" name="csg_generate" class="button button-alt"><?php echo csg_dynamic_button(); ?></button></a>
			</p>
		</form>

	</div>

	<form method="POST">

	<div class="welcome-to-csg submit-bg csg-dashboard-box">

			<h3><?php _e( 'Notify search engines', 'companion-sitemap-generator' ); ?></h3>

			<div class='welcome-column welcome-column-half'>

				<p><?php _e( 'We can notify search engines when changes are made to your sitemap', 'companion-sitemap-generator' ); ?>.</p>

				<p><label for="submit_google"><input type="checkbox" id="submit_google" name="submit_google" <?php if( csg_notify_engine( 'google' ) ) { echo "CHECKED"; } ?> > <?php echo sprintf( esc_html__( 'Notify %s', 'companion-sitemap-generator' ), 'Google' ); ?></label>&nbsp;&nbsp;</p>
				<p><label for="submit_bing"><input type="checkbox" id="submit_bing" name="submit_bing" <?php if( csg_notify_engine( 'bing' ) ) { echo "CHECKED"; } ?> > <?php echo sprintf( esc_html__( 'Notify %s', 'companion-sitemap-generator' ), 'Bing' ); ?></label></p>
				<p><label for="submit_yandex"><input type="checkbox" id="submit_yandex" name="submit_yandex" <?php if( csg_notify_engine( 'yandex' ) ) { echo "CHECKED"; } ?> > <?php echo sprintf( esc_html__( 'Notify %s', 'companion-sitemap-generator' ), 'Yandex' ); ?></label></p>

				<?php submit_button(); ?>

			</div><div class='welcome-column welcome-column-half'>

				<p><?php echo sprintf( esc_html__( 'You can also submit your xml sitemap to search engines to help them better crawl your site. Submit the following url: %s', 'companion-sitemap-generator' ), csg_sitemap_url() ); ?></p>

				<p>
					<a href="https://support.google.com/webmasters/answer/183668" target="_blank" class="button button-alt"><?php echo sprintf( esc_html__( 'Submit a sitemap to %s', 'companion-sitemap-generator' ), 'Google' ); ?></a>
				</p>
				<p>
					<a href="https://www.bing.com/webmaster/help/how-to-submit-sitemaps-82a15bd4" target="_blank" class="button button-alt"><?php echo sprintf( esc_html__( 'Submit a sitemap to %s', 'companion-sitemap-generator' ), 'Bing' ); ?></a>
				</p>

			</div>
			
		</div>

		<div class="welcome-to-csg settings-bg csg-dashboard-box">

			<div class='welcome-column welcome-column-quarter'>

				<h3><?php _e( 'Frequency', 'companion-sitemap-generator' ); ?></h3>
				<p class="description"><?php _e( 'How frequently the page is likely to change. This value provides general information to search engines and may not correlate exactly to how often they crawl the page.', 'companion-sitemap-generator' ); ?></p>
				<p><select name='frequency' style='width: 90%'>
					<option <?php if( changeFreq() == 'hide' ) { echo 'SELECTED'; } ?> value="hide"><?php _e( 'Hide frequency', 'companion-sitemap-generator' ); ?></option>
					<option <?php if( changeFreq() == 'always' ) { echo 'SELECTED'; } ?> value="always"><?php _e( 'Always', 'companion-sitemap-generator' ); ?></option>
					<option <?php if( changeFreq() == 'hourly' ) { echo 'SELECTED'; } ?> value="hourly"><?php _e( 'Hourly', 'companion-sitemap-generator' ); ?></option>
					<option <?php if( changeFreq() == 'daily' ) { echo 'SELECTED'; } ?> value="daily"><?php _e( 'Daily', 'companion-sitemap-generator' ); ?></option>
					<option <?php if( changeFreq() == 'weekly' ) { echo 'SELECTED'; } ?> value="weekly"><?php _e( 'Weekly', 'companion-sitemap-generator' ); ?></option>
					<option <?php if( changeFreq() == 'monthly' ) { echo 'SELECTED'; } ?> value="monthly"><?php _e( 'Monthly', 'companion-sitemap-generator' ); ?></option>
					<option <?php if( changeFreq() == 'yearly' ) { echo 'SELECTED'; } ?> value="yearly"><?php _e( 'Yearly', 'companion-sitemap-generator' ); ?></option>
					<option <?php if( changeFreq() == 'never' ) { echo 'SELECTED'; } ?> value="never"><?php _e( 'Never', 'companion-sitemap-generator' ); ?></option>
				</select></p>

			</div><div class='welcome-column welcome-column-quarter'>

				<h3><?php _e( 'Auto updating', 'companion-sitemap-generator' ); ?></h3>
				<p class="description"><?php _e( 'How often should the sitemap be updated? You can always update it manually. Settings only apply to the XML version, the HTML version is always up-to-date.', 'companion-sitemap-generator' ); ?></p>
				<p><select name='sitemap_schedule' id='sitemap_schedule' style='width: 90%'>
					<option value='never' <?php if( $sitemap_schedule == 'never' ) { echo "SELECTED"; } ?> ><?php _e( 'Never', 'companion-sitemap-generator' ); ?></option>
					<option value='hourly' <?php if( $sitemap_schedule == 'hourly' ) { echo "SELECTED"; } ?> ><?php _e( 'Hourly', 'companion-sitemap-generator' ); ?></option>
					<option value='twicedaily' <?php if( $sitemap_schedule == 'twicedaily' ) { echo "SELECTED"; } ?> ><?php _e( 'Twice Daily', 'companion-sitemap-generator' ); ?></option>
					<option value='daily' <?php if( $sitemap_schedule == 'daily' ) { echo "SELECTED"; } ?> ><?php _e( 'Daily', 'companion-sitemap-generator' ); ?></option>
				</select></p>

			</div><div class='welcome-column welcome-column-quarter'>

				<h3>
					<label for="use_sitemap_stylesheet"><?php _e( 'Sitemap styling', 'companion-sitemap-generator' ); ?> &nbsp;
					<input type="checkbox" name="use_sitemap_stylesheet" id="use_sitemap_stylesheet" <?php if( csg_use_XMLstylesheet() ) { echo "CHECKED"; } ?>></label>
				</h3>
				<p class="description"><?php _e( 'You can apply custom styling to your XML sitemap file to make it easier to read for humans. You can use our stylesheet or create your own.', 'companion-sitemap-generator' ); ?></p>
				<p>
					<span class='csg_tooltip' style='position: relative; bottom: -5px;'><span class="dashicons dashicons-editor-help"></span>
						<span class='csg_tooltip_text'><?php _e( 'Change the stylesheet for the XML sitemap, use full URL', 'companion-sitemap-generator' ); ?>.</span>
					</span>
					<input type="text" style="width: 90%" name="sitemap_stylesheet" id="sitemap_stylesheet" class="regular-text" value="<?php echo csg_XMLstylesheet(); ?>">
				</p>

			</div><div class='welcome-column welcome-column-quarter'>

				<h3>
					<label for="xml_in_html"><?php _e( 'Show XML in HTML', 'companion-sitemap-generator' ); ?> &nbsp;
					<input type="checkbox" name="xml_in_html" id="xml_in_html" <?php if( csg_xml_in_html() ) { echo "CHECKED"; } ?>></label>
				</h3>
				<p class="description"><?php _e( 'In case you\'d like to show a link to the XML version of the sitemap in the HTML sitemap you can add it here.', 'companion-sitemap-generator' ); ?></p>

			</div>

			<?php wp_nonce_field( 'csg_save_advanced' ); ?>	
			<?php submit_button(); ?>

		</div>

	</form>

	<div class="welcome-to-csg robots-bg csg-dashboard-box">
		
		<?php if( csg_has_robots() ) {

			// Read robots.txt file
			function csg_read_robots() {

				$robotLines 		= array();
				$readRobots 		= fopen( csg_robots(), "r");

				if ($readRobots) {
					while (($lineRobot = fgets($readRobots)) !== false) {
					    array_push( $robotLines, $lineRobot );
					}
					fclose($readRobots);
				}

				foreach ($robotLines as $robotLine) { 
					echo $robotLine; echo ''; 
				}
			}

			// Write to the robots.txt file
			function csg_write_robots( $contentToWrite = '' ) {

				if ( is_writable( csg_robots() ) ) {

				    if (!$handle = fopen( csg_robots() , 'w')) {
				         errorMSG("Cannot open file robots.txt");
				         exit;
				    }

				    if (fwrite($handle, $contentToWrite) === FALSE) {
				        errorMSG("Something went wrong.");
				        exit;
				    }

				    succesMSG( __( 'Your robots file has been updated succesfully. Be sure to reload the page before making further adjustments.', 'companion-sitemap-generator' ) );

				    fclose($handle);

				} else {
				    errorMSG( __( 'The file robots.txt is not writable', 'companion-sitemap-generator' ) );
				}
			}

			if( isset( $_POST['csg_saveRobots'] ) ) {
				check_admin_referer( 'csg_save_robots' );
				csg_write_robots( sanitize_textarea_field( $_POST['csg_robots_content'] ) ); 
			}

			?>

			<div class='welcome-column welcome-column-half'>

				<h3><?php _e( 'Edit Robots', 'companion-sitemap-generator' ); ?></h3>	
				<p class="description"><?php _e( 'While a sitemap allows search engines to scan pages faster, a robots.txt file disallows search engines from scanning certain pages.', 'companion-sitemap-generator' ); ?></p>	
				<br />
				<form method="POST">
					<textarea name="csg_robots_content" style="height: 300px; width: 100%;"><?php csg_read_robots(); ?></textarea>
					<?php wp_nonce_field( 'csg_save_robots' ); ?>	
					<p><button type='submit' name='csg_saveRobots' class='button button-primary'><?php _e( 'Save robots', 'companion-sitemap-generator' ); ?></button></p>
				</form>

			</div><div class='welcome-column welcome-column-half'>

				<h4 style="margin-bottom: 0;"><?php _e( 'Add your sitemap to robots', 'companion-sitemap-generator' ); ?></h4>
				<p class="description"><?php _e( 'Use this line if you\'d like to add the sitemap link to the robots file (good for SEO):', 'companion-sitemap-generator' ); ?></p>
				<p><code>Sitemap: <?php echo csg_sitemap_url(); ?></code></p>

				<br />
				<h4 style="margin-bottom: 0;"><?php _e( 'Basic Example', 'companion-sitemap-generator' ); ?></h4>
				<p class="description"><?php _e( 'Here\'s an example of what a robots file could look like.', 'companion-sitemap-generator' ); ?></p>

				<p>User-agent: *<br />
				Disallow: /wp-admin/<br />
				Disallow: /wp-includes/<br />
				Disallow: /feed/<br />
				Disallow: */feed/</p>
				
				<p><a href='https://support.google.com/webmasters/answer/6062608' rel='nofollow' target='_blank'><?php _e( 'Read more about robots', 'companion-sitemap-generator' ); ?></a></p>

			</div>

		<?php } else { 

			if( isset( $_POST['csg_createRobots'] ) ) {
				check_admin_referer( 'csg_create_robots' );
				csg_create_robots();
				header( "Location: ".admin_url( 'tools.php?page=csg-robots' ) ); 
			}

			?>

			<h3><?php _e( 'Edit Robots', 'companion-sitemap-generator' ); ?></h3>
			<p>
				<?php _e( 'In order to use the robots editor you\'ll first need to create a robots file.', 'companion-sitemap-generator' ); ?>
				<?php _e( 'This will create a physical robots.txt file in your root folder. ', 'companion-sitemap-generator' ); ?>
			</p>

			<form method="POST">
				<?php wp_nonce_field( 'csg_create_robots' ); ?>	
				<p><button type='submit' name='csg_createRobots' class='button button-primary'><?php _e( 'Create file', 'companion-sitemap-generator' ); ?></button></p>
			</form>

		<?php } ?>

	</div>

</div><div class="csg-column-small">

	<div class="welcome-to-csg help-bg csg-dashboard-box">
		<div class="welcome-column welcome-column.welcome-column-half">

			<h3 class="support-sidebar-title"><?php _e( 'Help' ); ?></h3>
			<ul class="support-sidebar-list">
				<li><a href="https://codeermeneer.nl/wp-admin/tools.php?page=csg-sitemap&tabbed=support#" target="_blank"><?php _e( 'Frequently Asked Questions', 'companion-sitemap-generator' ); ?></a></li>
				<li><a href="https://wordpress.org/support/plugin/companion-sitemap-generator" target="_blank"><?php _e( 'Support Forums' ); ?></a></li>
			</ul>

			<h3 class="support-sidebar-title"><?php _e( 'Want to contribute?', 'companion-sitemap-generator' ); ?></h3>
			<ul class="support-sidebar-list">
				<li><a href="http://codeermeneer.nl/contact/" target="_blank"><strong><?php _e( 'Give feedback', 'companion-sitemap-generator' ); ?></strong></a></li>
				<li><a href="https://translate.wordpress.org/projects/wp-plugins/companion-sitemap-generator/" target="_blank"><?php _e( 'Help us translate', 'companion-sitemap-generator' ); ?></a></li>
			</ul>

		</div>
		<div class="welcome-column welcome-column.welcome-column-half">

			<h3 class="support-sidebar-title"><?php _e( 'Developer?', 'companion-sitemap-generator' ); ?></h3>
			<ul class="support-sidebar-list">
				<li><a href="https://codeermeneer.nl/documentation/auto-update/" target="_blank"><?php _e( 'Documentation' ); ?></a></li>
			</ul>

		</div>
	</div>

	<div class="welcome-to-csg gutenberg-bg csg-dashboard-box">

		<h3><?php _e( 'Want to add the HTML sitemap to your page?', 'companion-sitemap-generator' ); ?></h3>
		<p><?php _e( "In version 4 we've removed the shortcode generator in favor of the new fancy gutenberg block. Just add the HTML sitemap block to your page.", "companion-sitemap-generator" ); ?></p>
		<p><?php _e( "Don't care for gutenberg?", "companion-sitemap-generator" ); ?> 
		<a href='https://codeermeneer.nl/documentation/how-to-use-the-shortcode-in-companion-sitemap-generator/' target='_blank'><?php _e( "You can still use the shortcode", "companion-sitemap-generator" ); ?>. </a></p>
	
	</div>

	<div class="welcome-to-csg support-bg csg-dashboard-box">
		<div class="welcome-column welcome-column">

			<h3><?php _e( 'Support', 'companion-sitemap-generator' ); ?></h3>
			<p><?php _e( 'Feel free to reach out to us if you have any questions or feedback.', 'companion-sitemap-generator' ); ?></p>
			<p><a href="https://codeermeneer.nl/contact/" target="_blank" class="button button-primary"><?php _e( 'Contact us', 'companion-sitemap-generator' ); ?></a></p>
			<p><a href="https://codeermeneer.nl/plugins/" target="_blank" class="button button-alt"><?php _e( 'Check out our other plugins', 'companion-sitemap-generator' ); ?></a></p>

		</div>
	</div>

	<div class="welcome-to-csg love-bg csg-show-love csg-dashboard-box">

		<h3><?php _e( 'Like our plugin?', 'companion-sitemap-generator' ); ?></h3>
		<p><?php _e( 'Companion Sitemap Generator is free to use. It has required a great deal of time and effort to develop and you can help support this development by making a small donation.<br />You get useful software and we get to carry on making it better.', 'companion-sitemap-generator' ); ?></p>
		<a href="https://wordpress.org/support/plugin/companion-sitemap-generator/reviews/#new-post" target="_blank" class="button button-alt">
			<?php _e( 'Rate us (5 stars?)', 'companion-sitemap-generator' ); ?>
		</a>
		<a href="<?php echo csg_donateUrl(); ?>" target="_blank" class="button button-primary">
			<?php _e( 'Donate to help development', 'companion-sitemap-generator' ); ?>
		</a>
		<p style="font-size: 12px; color: #BDBDBD;">Donations via PayPal. Amount can be changed.</p>

	</div>

</div>