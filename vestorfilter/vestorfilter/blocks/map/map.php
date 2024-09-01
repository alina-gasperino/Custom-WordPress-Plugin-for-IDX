<?php
/**
 * Plugin Name:       Vestor Filter Map
 * Description:       Adds an embedded map to your page content.
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           2.0.0
 * Author:            VestorFilters
 * License:           GPL-2.0
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vestor-map
 *
 * @package           vfmap
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/writing-your-first-block-type/
 */
function vfmap_vestor_map_block_init() {
	$dir = __DIR__;

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "vestorfilter/map" block first.'
		);
	}
	$index_js     = '/blocks/map/build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'map-block-editor',
		\VestorFilter\Plugin::$plugin_uri . $index_js,
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'map-block-editor', 'vestorfilter' );

	$editor_css = '/blocks/map/build/index.css';
	wp_register_style(
		'map-block-editor',
		\VestorFilter\Plugin::$plugin_uri . $editor_css,
		array(),
		$script_asset['version']
	);

	register_block_type(
		'vestorfilter/map',
		array(
			'editor_script'   => 'map-block-editor',
			'editor_style'    => 'map-block-editor',
			'style'           => 'map-block',
			'render_callback' => 'vfmap_vestor_map_block_render',
		)
	);
}
add_action( 'init', 'vfmap_vestor_map_block_init' );

function vfmap_vestor_map_block_render( $attrs ) {

	if ( empty( $attrs['filterParams'] ) || empty( $attrs['centerLat'] ) || empty( $attrs['centerLon'] ) ) {
		return '';
	}

	parse_str( $attrs['filterParams'], $filters );

	$height = (int) $attrs['height'] ?? 100;
	if ( $height < 50 ) {
		$height = 50;
	}
	if ( $height > 200 ) {
		$height = 200;
	}

	$args = [
		'filters' => $filters,
		'lat'     => (float) $attrs['centerLat'],
		'lon'     => (float) $attrs['centerLon'],
		'zoom'    => (int)   ( $attrs['zoom'] ?? 12 ),
		'labels'  => (bool)  ( $attrs['labels'] ?? false ),
	];

	$content  = '<div data-vestor-map="widget" class="vestorfilter-mini-map"><div style="--ratio:' . $height . '%" class="vestorfilter-mini-map__spacer"></div>';
	$content .= '<script type="application/json">' . json_encode( $args ) . '</script>';
	$content .= '</div>';

	return $content;

}
