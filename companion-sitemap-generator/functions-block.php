<?php

// Main output function
function htmlsitemap( string $orderby, string $sort, int $limit ) {

	// Load CSS
	wp_enqueue_style( 'sitemap-block' );

	// Classes array (for future support of custom classes (maybe))
	$_classes = [
		'companion-sitemap-block',
	];

	// Content
	$_content 	= [];
	$_content[] = html_posttypes( $sort, $orderby, $limit );
	$_content[] = html_taxonomies( $sort, $orderby, $limit );
	$_content[] = html_additionalpages( $sort, $orderby, $limit );

	// To string
	$block_classes = implode( " ", $_classes );
	$block_content = implode( " ", $_content );

	// Return output
	return sprintf(
		'<div class="%1$s">%2$s</div>',
		esc_attr( $block_classes ),
		$block_content
	);
}

// Block and Shortcode init
add_action( 'init', function() {

	// Register frontend CSS
	wp_register_style(
		'sitemap-block',
		plugins_url( 'frontend/style.css', __FILE__ ),
		[],
		filemtime( dirname( __FILE__ ) . "/frontend/style.css" ),
		"all"
	);

	if( !function_exists( 'register_block_type' ) ) {
		return;
	}

	// Register the block script
	wp_register_script(
		'sitemap-backend-script',
		plugins_url( 'backend/block.js', __FILE__ ),
		[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor' ],
		filemtime( dirname( __FILE__ ) . "/backend/block.js" ),
	);

	// Register the block CSS
	wp_register_style(
		'sitemap-backend-style',
		plugins_url( 'backend/editor.css', __FILE__ ),
		[ 'wp-edit-blocks' ],
		filemtime( dirname( __FILE__ ) . "/backend/editor.css" ),
		"all"
	);

	// Register the block
	register_block_type( 'sitemap/block',
		[
			'editor_script' 	=> 'sitemap-backend-script',
			'editor_style' 		=> 'sitemap-backend-style',
			'render_callback' 	=> 'htmlsitemap_block_handler',
			'attributes' 		=> [
				'columns' 	=> [ 'default' => '1', 'type' => 'string' ],
				'orderby' 	=> [ 'default' => 'date', 'type' => 'string' ],
				'sort' 		=> [ 'default' => 'asc', 'type' => 'string' ],
				'limit' 	=> [ 'default' => '-1', 'type' => 'string' ]
			]
		]
	);
});

// Block handler
function htmlsitemap_block_handler( array $attributes ) {
	return htmlsitemap(
		sanitize_text_field( $attributes['orderby'] ),
		sanitize_text_field( $attributes['sort'] ),
		sanitize_text_field( $attributes['limit'] )
	);
}

// Create the shortcode
add_shortcode( 'html-sitemap', function( $attributes ) {

	$conf = shortcode_atts([
		'orderby'		=> 'date',
		'sort'			=> 'asc',
		'limit'			=> '-1',
	], $attributes, 'html-sitemap' );

	return htmlsitemap(
		sanitize_text_field( $conf['orderby'] ),
		sanitize_text_field( $conf['sort'] ),
		sanitize_text_field( $conf['limit'] )
	);

});
