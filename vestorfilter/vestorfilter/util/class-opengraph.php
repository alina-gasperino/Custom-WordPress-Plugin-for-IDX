<?php

namespace VestorFilter\Util;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class OpenGraph extends Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public function install() {

		add_action( 'wp_head', array( $this, 'output_tags' ) );

	}

	public function output_tags() {

		$tags = apply_filters( 'vestorfilter_og_tags', [] );

		if ( empty( $tags ) ) {
			$post = get_queried_object();
			
			if ( empty( $post ) || empty( $post->ID ) ) {
				return;
			}

			$tags['title']       = get_the_title( $post );
			$tags['description'] = get_the_excerpt( $post );
			$tags['url']         = get_permalink( $post );
			$tags['type']        = 'website';
			if ( has_post_thumbnail( $post ) ) {
				$tags['image'] = get_the_post_thumbnail_url( $post );
			}

			$tags = apply_filters( 'vestorfilter_post_og_tags', $tags, $post );

		}

		if ( is_array( $tags ) ) {

			foreach( $tags as $property => $value ) {
				printf( '<meta property="og:%s" content="%s" />', $property, $value );
			}

		}

	}


}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Util\OpenGraph', 'init' ) );
