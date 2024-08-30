<?php

namespace Util;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

abstract class Singleton {

	public function __construct() {}

	public function get_class_namespace() {

		$class_name = get_class( $this );
		$class_name = strtolower( $class_name );
		$class_name = str_replace( "\\", '_', $class_name );

		return $class_name;
	}

	public static function init() {

		$self = new static();
		$self = apply_filters( $self->get_class_namespace() . '_instance', $self );

		$self->setup();
		$self->install();

		do_action( $self->get_class_namespace() . '_installed', $self );
		do_action( $self->get_class_namespace() . '_init', $self );

		return $self;

	}

	public function install() {}

	private function setup() {

		if ( ! empty( static::$instance ) ) {
			return static::$instance;
		}

		static::$instance = $this;

	}

	public static function instance() {
		return static::$instance;
	}

}