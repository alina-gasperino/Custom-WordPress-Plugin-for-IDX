<?php

namespace VestorFilter;

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function team_member_block_init() {
	$dir = __DIR__;

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "vestorfilter/team-member" block first.'
		);
	}
	$index_js     = '/blocks/team-member/build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'team-member-block-editor',
		Plugin::$plugin_uri . $index_js,
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'team-member-block-editor', 'vestorfilter' );

	$editor_css = '/blocks/team-member/build/index.css';
	wp_register_style(
		'team-member-block-editor',
		Plugin::$plugin_uri . $editor_css,
		array(),
		$script_asset['version']
	);

	register_block_type(
		'vestorfilter/team-member',
		array(
			'editor_script'   => 'team-member-block-editor',
			'editor_style'    => 'team-member-block-editor',
			'style'           => 'team-member-block',
			'render_callback' => 'VestorFilter\render_team_member_block',
		)
	);
}
add_action( 'init', 'VestorFilter\team_member_block_init' );

function render_team_member_block( $atts, $content ) {

	if ( preg_match( '/<p class="wp-block-vestorfilter-team-member--subtitle">(.*?)<\/p>/ms', $content, $match ) ) {
		
		$text = str_replace( "\n", "<br>", $match[1] );
		$content = str_replace( $match[0], '<p class="wp-block-vestorfilter-team-member--subtitle">' . $text . '</p>', $content );
	}

	$order = rand( 1, 999999 );

	$content = str_replace( 
		'class="wp-block-vestorfilter-team-member"', 
		'class="wp-block-vestorfilter-team-member" style="--order:' . $order . '"', 
		$content
	);

	$content = str_replace( '<a ', '<a target="_blank" ', $content );

	return $content;

}