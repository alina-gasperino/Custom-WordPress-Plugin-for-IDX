<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Social extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public static $networks_available = [];

	public function install() {

		self::$networks_available['facebook']  = '<a class="%2$s" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=%3$s">%1$s</a>';
		self::$networks_available['twitter']   = '<a class="%2$s" target="_blank" href="https://twitter.com/intent/tweet?url=%3$s&text=%4$s">%1$s</a>';
		self::$networks_available['linkedin']  = '<a class="%2$s" target="_blank" href="http://www.linkedin.com/shareArticle?mini=true&url=%3$s&title=%4$s">%1$s</a>';
		self::$networks_available['pinterest'] = '<a class="%2$s" target="_blank" href="http://pinterest.com/pin/create/button/?url=%3$s&media=%6$s&description=%4$s">%1$s</a>';

		self::$networks_available = apply_filters( 'vestorfilter_social_networks', self::$networks_available );

	}

	public static function get_network_tag( $network, $args ) {

		$tag = self::$networks_available[ $network ] ?? '';

		if ( is_callable( $tag ) ) {
			$tag = $tag( $args );
		}

		$tag = apply_filters( 'vestorfilter_social_share_tag__' . $network, $tag, $args );

		if ( ! empty( $args['image'] ) ) {
			if ( is_numeric( $args['image'] ) ) {
				$img_src = wp_get_attachment_image_src( $args['image'], 'social-share-thumbnail' );
				if ( $img_src ) {
					$img_src = urlencode( $img_src[0] );
				}
			} else {
				$img_src = $args['image'];
			}
		}

		$icon = apply_filters( 'vestorfilter_social_share_icon', use_icon('share-' . $network), $network, $args );

		$tag = sprintf(
			$tag,
			$icon,
			esc_attr( $args['classes'] ?? '' ),
			$args['url'] ?? '',
			urlencode( $args['title'] ?? '' ),
			urlencode( $args['description'] ?? '' ),
			! empty( $img_src ) ? $img_src : ''
		);

		return $tag;
	}

	public static function get_share_links( $args ) {

		global $wp;

		$args = wp_parse_args(
			$args,
			[
				'networks' => array_keys( self::$networks_available ),
				'url'      => add_query_arg( $wp->query_vars, home_url( $wp->request ) ),
				'image'    => null,
				'title'    => get_bloginfo( 'title' ),
				'desc'     => get_bloginfo( 'description' ),
			]
		);

		$networks = [];

		foreach ( $args['networks'] as $network ) {
			array_push(
				$networks,
				self::get_network_tag(
					$network,
					[
						'title'       => $args['title'],
						'description' => $args['desc'],
						'image'       => $args['image'],
						'url'         => $args['url'],
						'classes'     => apply_filters( 'vestorfilter_social_share_tag_classes', 'vestorfilter-share network__' . $network, $network, $args ),
					]
				)
			);
		}

		return $networks;

	}

}

add_action( 'vestorfilter_installed', [ 'VestorFilter\Social', 'init' ] );
