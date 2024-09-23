<?php

// Donate url
function csg_donateUrl() {
	return 'https://www.paypal.me/dakel/10/';
}

// Get true or false
function csg_true_or_false( $check_value ) {

	global $wpdb;

	$table_name = $wpdb->prefix . "csg_sitemap"; 
	$configs 	= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s", $check_value ) );

	foreach ( $configs as $config ) {
		$tof = $config->onoroff;
	}

	return ( $tof == 'on' ) ? true : false;

}

// Show XML in HTML?
function csg_xml_in_html() {
	return csg_true_or_false( 'xml_in_html' );
}

// The XML stylesheet
function csg_use_XMLstylesheet() {
	return csg_true_or_false( 'use_sitemap_stylesheet' );
}

function csg_XMLstylesheet() {

	global $wpdb;

	$table_name = $wpdb->prefix . "csg_sitemap"; 
	$configs 	= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s", "sitemap_stylesheet" ) );

	foreach ( $configs as $config ) {
		$stylesheet = $config->onoroff;
	}

	return ( $stylesheet != '' ) ? $stylesheet : plugin_dir_url( __FILE__ ) . 'sitemap.xsl';

}

// Temporary check for main site in multisite
function csg_mainSiteOnly() {
	return ( is_multisite() && !is_main_site() ) ? false : true;
}

// The url of the sitemap
function csg_sitemap_url() {
	return is_multisite() ? network_site_url() . csg_sitemap_file( true, get_current_blog_id() ) : site_url() . '/' . csg_sitemap_file();
}

// Get the correct sitemap file
function csg_sitemap_file( $multisite = false, $blogid = '1' ) {
	return ( !$multisite OR $blogid == '1' ) ? 'sitemap.xml' : 'sitemap-'.$blogid.'.xml';
}

// Get post types
function csg_get_posttypes() {
	$post_types = get_post_types( array( 'public' => true ), 'names', 'and' ); // Get all post types
	unset( $post_types["attachment"] ); // Remove attachment
	return $post_types;
}

// Get taxonomies
function csg_get_taxonomies() {

	// Global array
	$taxes = array();

	foreach( array( true, false ) as $builtin ) {

		// Base arguments
		$csg_term_args = array( 'public' => true, '_builtin' => $builtin );

		// If needed add multilingual
		if( csg_is_multilingual() ) {
			$csg_term_args['lang'] = csg_default_language();
		}

		// Push them to an array
		foreach( get_taxonomies( $csg_term_args, 'names', 'and' ) as $taxonomie ) $taxes[] = $taxonomie;

	}

	// Return global array
	return $taxes;
}

// Creates the sitemap
function csg_sitemap() {

	$csg_stm_file 		= is_multisite() ? csg_sitemap_file( true, get_current_blog_id() ) :  csg_sitemap_file();
	$csg_sitemap_file 	= ABSPATH . $csg_stm_file;

	if ( file_exists( $csg_sitemap_file ) ) {

		if ( is_writable( $csg_sitemap_file ) ) {

				// First clear + write sitemap
				file_put_contents( $csg_sitemap_file, '' );
				file_put_contents( $csg_sitemap_file, csg_sitemap_content() );

				csg_notify_engines(); // Notify search engines

				// Succes
				succesMSG( '<b>'.__( 'Your sitemap has been updated', 'companion-sitemap-generator' ).'</b> '.__( 'Check it out', 'companion-sitemap-generator' ).': <a href="'. csg_sitemap_url() .'" target="_blank">'. csg_sitemap_url() .'</a>' );

		} else {

			// Error when sitemap.xml is not writable
		    errorMSG( __( 'Your sitemap file is not writable', 'companion-sitemap-generator') );

		}

	} else {

		// Create the sitemap file
		fopen( $csg_sitemap_file, 'w+' );
		errorMSG( __( 'We had to create a file first, please update the sitemap again.', 'companion-sitemap-generator' ) );

	}

}

function csg_proper_url_format( $url ) {
	return str_replace( '&', '%26', $url );
}

// Get addition pages
function csg_get_additionalpages() {

	global $wpdb;
	$table_name = $wpdb->prefix . "csg_sitemap"; 
	$pages 		= array();

	// Enable for major updates
	$configs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s", "additionalpages" ) );
	foreach( $configs as $config ) {
		if( isset( $config->onoroff ) ) $pages[] = $config->onoroff;
	}

	return $pages;

}
function csg_get_additionalpages__textarea() {
	$pages = csg_get_additionalpages();
	if( !empty( $pages ) ) {
		foreach( $pages as $page ) { 
			echo $page."\n"; 
		}
	}
}

// Get the frequency
function changeFreq() {

	global $wpdb;
	$table_name = $wpdb->prefix . "csg_sitemap"; 

	// Enable for major updates
	$configs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s", "frequency" ) );
	foreach ( $configs as $config ) {
		return $config->onoroff;
	}

}

function csg_sitemap_line( $id ) {

	$csg_sitemap_content = '<url>
		<loc>'. csg_proper_url_format( get_the_permalink( $id ) ) .'</loc>';
	    if( csg_is_multilingual() ) {
		    foreach ( csg_languages() as $key => $lang ) {
		    	if( $lang != csg_default_language() ) {
		    		if( csg_post_translation_id( $id, $lang ) != '' ) {
			    		$csg_sitemap_content .= '
		<xhtml:link 
			rel="alternate" 
			hreflang="'.$lang.'" 
			href="'.csg_proper_url_format( get_the_permalink( csg_post_translation_id( $id, $lang ) ) ).'"
			/>';
			    	}
		    	}
		    }
	    }
		$csg_sitemap_content .= '
		<lastmod>'.get_the_modified_date( 'Y-m-d', $id ).'</lastmod>';
	    if( changeFreq() != 'hide' ) $csg_sitemap_content .= '<changefreq>'.changeFreq().'</changefreq>';
	    $csg_sitemap_content .= csg_sitemap_line_images( $id );
	$csg_sitemap_content .= '</url>';

	return $csg_sitemap_content;

}


function csg_sitemap_line_additionalpages( $link ) {

	$csg_sitemap_content = '<url>
		<loc>'. csg_proper_url_format( $link ) .'</loc>';
	    if( csg_is_multilingual() ) {
		    foreach ( csg_languages() as $key => $lang ) {
		    	if( $lang != csg_default_language() ) {
		    		if( csg_post_translation_id( get_the_ID(), $lang ) != '' ) {
			    		$csg_sitemap_content .= '
		<xhtml:link 
			rel="alternate" 
			hreflang="'.$lang.'" 
			href=""
			/>';
			    	}
		    	}
		    }
	    }
		$csg_sitemap_content .= '
		<lastmod>'.get_the_modified_date( 'Y-m-d' ).'</lastmod>';
	    if( changeFreq() != 'hide' ) $csg_sitemap_content .= '<changefreq>'.changeFreq().'</changefreq>';
	$csg_sitemap_content .= '</url>';

	return $csg_sitemap_content;

}

function csg_sitemap_line_terms( $id ) {

	$csg_sitemap_content = '<url>
		<loc>'. csg_proper_url_format( get_term_link( $id ) ) .'</loc>';
	    if( csg_is_multilingual() ) {
		    foreach ( csg_languages() as $key => $lang ) {
		    	if( $lang != csg_default_language() ) {
		    		if( csg_term_translation_id( $id, $lang ) != '' ) {
			    		$csg_sitemap_content .= '
		<xhtml:link 
			rel="alternate" 
			hreflang="'.$lang.'" 
			href="'.csg_proper_url_format( get_term_link( csg_term_translation_id( $id, $lang ) ) ).'"
			/>';
			    	}
		    	}
		    }
	    }
	    if( changeFreq() != 'hide' ) $csg_sitemap_content .= '<changefreq>'.changeFreq().'</changefreq>';
	$csg_sitemap_content .= '</url>';

	return $csg_sitemap_content;

}

// Get all images by a post
function csg_sitemap_line_images( $post_id ) { 

	$csg_line = '';

	if( !in_array( 'media', csg_exclude_posttypes() ) ) {

		$images 		= array();
		$attachments 	= get_children( array(
				'post_parent' 		=> $post_id,
	            'post_status' 		=> 'inherit',
	            'post_type' 		=> 'attachment',
	            'post_mime_type' 	=> 'image',
	            'posts_per_page' 	=> '1000' // Sitemaps can only have up to a 1000 images per page (do you need more really?)
	        )
		);

		foreach( $attachments as $att_id => $attachment ) {
			$csg_line .= '
			<image:image>
		    	<image:loc>'.csg_proper_url_format( wp_get_attachment_url( $attachment->ID ) ).'</image:loc>
		    </image:image>';

		}

	}

	return $csg_line;

}

// This function writes to the sitemap file
function csg_sitemap_content() {

	// Basic XML output
	$csg_sitemap_content = '<?xml version="1.0" encoding="UTF-8"?>';

	// Use stylesheet?
	if( csg_use_XMLstylesheet() ) {
		$csg_sitemap_content .= '<?xml-stylesheet type="text/xsl" href="'.csg_XMLstylesheet().'"?>';
	}

	// Urlset
	$csg_sitemap_content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

	// Add post types
	$limit 	= 5000; // Had to limit it to 5000 because it kept crashing in some cases, will try and improve performance to be able to increase this number
	$posts 	= get_posts( array( 'order' => 'asc', 'orderby' => 'name','posts_per_page' => $limit, 'post_status' => 'publish', 'post__not_in' => csg_exclude(),'post_type' => array_diff( csg_get_posttypes(), csg_exclude_posttypes() ) ) );
	if( $posts ) {
		foreach( $posts as $post ) {
			$csg_sitemap_content .= csg_sitemap_line( $post->ID );
		}
		wp_reset_postdata();
	}

	// Add taxonomies 
	$taxonomies = array_diff( csg_get_taxonomies(), csg_exclude_posttypes() );
	if ( $taxonomies ) {
		foreach( $taxonomies as $taxonomie ) {
			$get_terms = get_terms( array( 'taxonomy' => $taxonomie, 'orderby' => 'name', 'order' => 'asc', 'hide_empty' => true, 'exclude' => csg_exclude_ctam() ) );
			foreach( $get_terms as $tax ) {
				$csg_sitemap_content .= csg_sitemap_line_terms( $tax->term_id );
			}
		}
	}

	// Add Additional pages
	if ( !empty( csg_get_additionalpages() ) ) {
		foreach( csg_get_additionalpages() as $additionalpage ) {
			if( $additionalpage != '' ) $csg_sitemap_content .= csg_sitemap_line_additionalpages( sanitize_text_field( $additionalpage ) );
		}
	}

	$csg_sitemap_content .= '</urlset>';

	return ent2ncr( $csg_sitemap_content );

}

// Custom functions for handling messages
function succesMSG( $content ) {
	echo '<div id="message" class="updated"><p>'.$content.'</p></div>';
}
function errorMSG( $content ) {
	echo '<div id="message" class="error"><p>'.$content.'</p></div>';
}

// Get all post types for html sitemap
function html_posttypes( $sort, $orderby, $numberofcolumns, $limit ) {

	$posts 			= '';
	$columns 		= esc_attr( $numberofcolumns );
	$post_types 	= array_diff( csg_get_posttypes(), csg_exclude_posttypes() );

	foreach( $post_types  as $post_type ) {

		$csg_sitemap_args = array( 
			'order' 			=> $sort, 
			'post_type' 		=> $post_type, 
			'posts_per_page' 	=> $limit, 
			'post_status' 		=> 'publish', 
			'post__not_in' 		=> csg_exclude(),
			'orderby'			=> $orderby,
			'post_parent' 		=> 0,
		);
			
		$csg_sitemap_posts = get_posts( $csg_sitemap_args );

		if ( $csg_sitemap_posts ) {

			$get_post_type = get_post_type_object( $post_type ); 

			$posts .= "<div class='sitemap-column sitemap-columns-{$columns} sitemap-posttypes'><div class='html-sitemap-column'><h2>{$get_post_type->label}</h2><ul>";

			if( is_post_type_hierarchical( $post_type ) ) {
				$csg_sitemap_args['echo'] 		= false;
				$csg_sitemap_args['title_li'] 	= false;
				$posts .= wp_list_pages( $csg_sitemap_args );

			} else {
				foreach( $csg_sitemap_posts as $post ) {
					$id 		= $post->ID;
					$title 		= get_the_title( $id );
					$permalink 	= get_the_permalink( $id );
					$posts .= "<li class='{$post_type}_item {$post_type}-item-{$id}'><a href='{$permalink}'>{$title}</a></li>";
				}
				wp_reset_postdata();
			}
			$posts .= "</ul></div></div>";


		}

	}

	return $posts;

}

// Taxonomies
function html_taxonomies( $sort, $orderby, $numberofcolumns ) {

	$return 		= '';
	$columns 		= esc_attr( $numberofcolumns );
	$taxonomies 	= array_diff( csg_get_taxonomies(), csg_exclude_posttypes() );

	if ( $taxonomies ) {

		foreach( $taxonomies as $taxonomie ) {

			$thisTaxonomie 	= get_taxonomy( $taxonomie );
			$terms 			= get_terms( array( 'taxonomy' => $taxonomie, 'orderby' => $orderby, 'order' => $sort, 'hide_empty' => true, 'exclude' => csg_exclude_ctam() ) );

			if( !empty( $terms ) ) {

				$return .= "<div class='sitemap-column sitemap-columns-{$columns} sitemap-posttypes'><div class='html-sitemap-column'><h2>{$thisTaxonomie->label}</h2><ul>";

				foreach( $terms as $tax ) {
					$id 		= $tax->term_id;
					$title 		= $tax->name;
					$permalink 	= get_term_link( $id );
					$return 	.= "<li class='{$post_type}_item {$post_type}-item-{$id}'><a href='{$permalink}'>{$title}</a></li>";
				}

				$return .= "</ul></div></div>";


			}

		}

	}

	return $return;

}

// Aditional pages
function html_additionalpages( $sort, $orderby, $numberofcolumns ) {

	$return 				= '';
	$columns 				= esc_attr( $numberofcolumns );
	$additionalpages 		= csg_get_additionalpages();
	if( csg_xml_in_html() ) $additionalpages[] = csg_sitemap_url();

	if ( !empty( $additionalpages ) && $additionalpages[0] != '' ) {

		$additionalpages_label = __( 'Additional pages', 'companion-sitemap-generator' );
		$return .= "<div class='sitemap-column sitemap-columns-{$columns} sitemap-posttypes'><div class='html-sitemap-column'><h2>{$additionalpages_label}</h2><ul>";

		foreach( $additionalpages as $additionalpage ) {
			$page = sanitize_text_field( $additionalpage );
			$return .= "<li class='additional_item'><a href='{$page}'>{$page}</a></li>";
		}

		$return .= "</ul></div></div>";

	}

	return $return;

}

// Create shortcode
function htmlsitemap_handler( $attributes ) {

	$conf = shortcode_atts([
		'columns' 		=> '1',
		'orderby'		=> 'date',
		'sort'			=> 'asc',
		'limit'			=> '-1',
	], $attributes, 'html-sitemap' );

	return htmlsitemap( sanitize_text_field( $conf['columns'] ), sanitize_text_field( $conf['orderby'] ), sanitize_text_field( $conf['sort'] ), sanitize_text_field( $conf['limit'] ) );
}

function htmlsitemap( $numberofcolumns, $orderby, $sort, $limit ) {

	wp_enqueue_style( 'csg-styling' );

	$classes 				= ( $numberofcolumns != '1' ) ? 'has-multiple-columns' : 'single-column';

	$csg_sitemap_content 	= '<div id="html_sitemap" class="'.$classes.'">';
	$csg_sitemap_content 	.= html_posttypes( $sort, $orderby, sanitize_text_field( $numberofcolumns ), $limit );
	$csg_sitemap_content 	.= html_taxonomies( $sort, $orderby, sanitize_text_field( $numberofcolumns ) );
	$csg_sitemap_content 	.= html_additionalpages( $sort, $orderby, sanitize_text_field( $numberofcolumns ) );
	$csg_sitemap_content 	.= '</div>';

	return $csg_sitemap_content;
}
add_shortcode( 'html-sitemap' , 'htmlsitemap_handler' );

// Exclude these posts from the sitemap
function csg_exclude() {

	global $wpdb;
	$table_name 	= $wpdb->prefix . "csg_sitemap"; 
	$config 		= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s", "exclude" ) );

	$list 			= $config[0]->onoroff;
	$list 			= explode( ", ", $list );
	$returnList 	= array();

	foreach ( $list as $key ) if( $key != '' ) array_push( $returnList, $key );
	
	return $returnList;

}

// Exclude these posttypes from the sitemap
function csg_exclude_posttypes() {

	global $wpdb;
	$table_name 	= $wpdb->prefix . "csg_sitemap"; 
	$config 		= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s", "posttypes" ) );

	$list 			= $config[0]->onoroff;
	$list 			= explode( ", ", $list );
	$returnList 	= array();

	foreach ( $list as $key ) if( $key != '' ) array_push( $returnList, $key );
	
	return $returnList;

}

// Exclude these categories, tags and more
function csg_exclude_ctam() {

	global $wpdb;
	$table_name 	= $wpdb->prefix . "csg_sitemap"; 
	$config 		= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s", "ctam" ) );

	$list 			= $config[0]->onoroff;
	$list 			= explode( ", ", $list );
	$returnList 	= array();

	foreach ( $list as $key ) if( $key != '' ) array_push( $returnList, $key );
	
	return $returnList;

}

// Get active tab
function csg_active_tab( $page ) {

	$cur_page = !isset( $_GET['tabbed'] ) ? '' : $_GET['tabbed'];

	if( $page == $cur_page ) {
		echo 'nav-tab-active';
	}

}

// Read sitemap.xml file
function csg_read_sitemap() {

	$return 			= '';
	$csg_stm_file 		= is_multisite() ? csg_sitemap_file( true, get_current_blog_id() ) :  csg_sitemap_file();
	$sitemapFile 		= ABSPATH . $csg_stm_file;

	$sitemapLines 		= array();
	$readSitemap 		= fopen( $sitemapFile, "r" );

	if( $readSitemap ) {
		while( ( $lineSitemap = fgets( $readSitemap ) ) !== false ) {
		    array_push( $sitemapLines, $lineSitemap );
		}
		fclose( $readSitemap );
	}

	foreach( $sitemapLines as $sitemapLine ) { 
		$return .= $sitemapLine;
	}

	return $return;
}

// Have changes been made to the sitemap?
function csg_changes_made() {
	return ( csg_read_sitemap() != csg_sitemap_content() ) ? true : false;
}

// Submit sitemap to search engines
function csg_notify_engine( $engine ) {

	global $wpdb;
	$table_name 	= $wpdb->prefix . "csg_sitemap"; 
	$getEngine 		= 'ping_'.$engine;
	$config 		= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s", $getEngine ) );
	$val 			= $config[0]->onoroff;

	return ( $val != '' ) ? true : false;

}
function csg_notify_engines() {

	// Check if changes are made
	if( csg_changes_made() ) {

		// Notify Google
		if( csg_notify_engine( 'google' ) ) {

	      	$url 	= "https://www.google.com/ping?sitemap=".csg_sitemap_url();
			$ch 	= curl_init();
			Curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		  	$result = curl_exec($ch);
		  	Curl_close( $ch );

		}

		// Notify Bing
		if( csg_notify_engine( 'bing' ) ) {

			$url 	= "http://www.bing.com/ping?sitemap=".csg_sitemap_url();
			$ch 	= curl_init();
			Curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		  	$result = curl_exec($ch);
		  	Curl_close( $ch );

		}

		// Notify Yandex
		if( csg_notify_engine( 'yandex' ) ) {

			$url 	= "http://webmaster.yandex.com/site/map.xml?host=".csg_sitemap_url();
			$ch 	= curl_init();
			Curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		  	$result = curl_exec($ch);
		  	Curl_close( $ch );

		}


	}

}

// Is multilingual 
function csg_is_multilingual() {
	return ( in_array( 'polylang/polylang.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) ? true : false;
}

// List of all languages
function csg_languages() {
	return pll_languages_list();
}

// Default language
function csg_default_language() {
	return pll_default_language();
}

// Get ID of stranslation for post
function csg_post_translation_id( $id, $lang ) {
	return pll_get_post( $id, $lang );
}
// Get ID of stranslation for term / category
function csg_term_translation_id( $id, $lang ) {
	return pll_get_term( $id, $lang );
}
function csg_get_term_language( $id ) {
	return pll_get_term_language( $id );
}
