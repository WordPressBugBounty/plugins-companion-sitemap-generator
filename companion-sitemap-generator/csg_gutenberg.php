<?php

// Let's set up the gutenberg block :)
function csg_sitemap_block() {

	$block_dir 		= dirname( __FILE__ );
	$block_file 	= 'backend/block.js';

	// Script
	wp_register_script(
		'sitemap_block_script',
		plugins_url( $block_file, __FILE__ ),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-components',
			'wp-editor'
		),
		filemtime( "$block_dir/$block_file" )
	);

	// Styles
	wp_register_style(
		'sitemap_block_style',
		plugins_url( 'backend/editor.css', __FILE__ ),
		array( 'wp-edit-blocks' )
	);

	// Register the block
	register_block_type( 'sitemap/block', 
		array(
			'editor_script' 	=> 'sitemap_block_script',
			'editor_style' 		=> 'sitemap_block_style',
			'render_callback' 	=> 'htmlsitemap_block_handler',
			'attributes' 		=> [
				'columns' 	=> [ 'default' => '1', 'type' => 'string' ],
				'orderby' 	=> [ 'default' => 'date', 'type' => 'string' ],
				'sort' 		=> [ 'default' => 'asc', 'type' => 'string' ],
				'limit' 	=> [ 'default' => '-1', 'type' => 'string' ]
			]
		)
	);
}
add_action( 'init', 'csg_sitemap_block' );

// Block handler
function htmlsitemap_block_handler( $attributes ) {
	return htmlsitemap( $attributes['columns'], $attributes['orderby'], $attributes['sort'], $attributes['limit'] );
}
