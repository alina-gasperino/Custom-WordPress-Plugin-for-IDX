<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Agent {

	private $meta = [];

	public function __construct( $post ) {

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		$this->meta = self::parse_meta( get_post_custom( $post->ID ) );
		$this->post = $post;

	}

	public static function parse_meta( $data ) {

		$meta = [];

		foreach( $data as $key => $values ) {

			if ( substr( $key, 0, 6 ) != '_agent' ) {
				continue;
			}

			$key = str_replace( '-', '_', sanitize_title( substr( $key, 7 ) ) );

			if ( count( $values ) > 1 ) {
				$meta[ $key ] = $values;
			} else {
				$meta[ $key ] = array_pop( $values );
			}
			
		}

		return $meta;

	}

	public function get_image( $size = 'thumbnail' ) {
		return get_post_thumbnail( $this->post, $size );
	}

	public function get_image_url( $size = 'thumbnail' ) {
		return get_the_post_thumbnail_url( $this->post, $size );
	}

	public function get_meta( $key, $throw_error = false ) {

		if ( ! isset( $this->meta[ $key ] ) ) {
			if ( $throw_error ) {
				throw new \Exception( "The meta key `$key` does not exist for this agent." );
			}
			return '';
		}

		return $this->meta[ $key ];
	}

	public function get_full_name() {

		return $this->get_meta( 'fname' ) . ' ' . $this->get_meta( 'lname' );

	}

}
